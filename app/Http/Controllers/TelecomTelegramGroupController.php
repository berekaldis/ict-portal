<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\TelegramGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TelecomTelegramGroupController extends Controller
{
    private const GROUP_TYPES = ['Group', 'Supergroup', 'Channel'];
    private const CATEGORIES  = ['Operations', 'HR', 'IT', 'Marketing', 'Finance', 'Branch', 'Management', 'Logistics', 'Customer Service', 'Training', 'Other'];
    private const STATUSES    = ['Active', 'Inactive', 'Archived'];

    public function index(Request $request): Response
    {
        $query = TelegramGroup::query()
            ->with(['branch', 'department', 'creator:id,name']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('chat_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('branch', fn ($bq) => $bq->where('branch_name', 'like', "%{$search}%"))
                    ->orWhereHas('department', fn ($dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($type = $request->query('group_type')) {
            $query->where('group_type', $type);
        }

        if ($branchId = $request->query('branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $perPage = (int) $request->query('per_page', 15);
        $groups  = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Calculate statistics
        $stats = [
            'total'    => TelegramGroup::count(),
            'active'   => TelegramGroup::where('status', 'Active')->count(),
            'inactive' => TelegramGroup::where('status', 'Inactive')->count(),
            'archived' => TelegramGroup::where('status', 'Archived')->count(),
        ];

        return Inertia::render('telecom/telegram-groups/Index', [
            'groups'      => $groups,
            'stats'       => $stats,
            'branches'    => Branch::where('is_active', true)->orderBy('branch_name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'groupTypes'  => self::GROUP_TYPES,
            'categories'  => self::CATEGORIES,
            'statuses'    => self::STATUSES,
            'filters'     => $request->only(['search', 'status', 'category', 'group_type', 'branch_id', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('telecom/telegram-groups/Create', [
            'branches'    => Branch::where('is_active', true)->orderBy('branch_name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'groupTypes'  => self::GROUP_TYPES,
            'categories'  => self::CATEGORIES,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'chat_id'       => 'nullable|string|max:100|unique:telegram_groups,chat_id',
            'username'      => 'nullable|string|max:100',
            'group_type'    => 'required|string|in:' . implode(',', self::GROUP_TYPES),
            'category'      => 'nullable|string|max:100',
            'purpose'       => 'nullable|string|max:255',
            'description'   => 'nullable|string',
            'branch_id'     => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'managed_by'    => 'nullable|string|max:255',
            'status'        => 'required|string|in:' . implode(',', self::STATUSES),
            'invite_link'   => 'nullable|url|max:500',
            'is_public'     => 'boolean',
            'member_count'  => 'nullable|integer|min:0',
            'notes'         => 'nullable|string',
        ]);

        $validated['created_by'] = Auth::id();

        $group = TelegramGroup::create($validated);

        if (!empty($validated['invite_link'])) {
            $this->generateQrCode($group);
        }

        return redirect()->route('telecom.telegram-groups.index')
            ->with('success', "Telegram group \"{$group->name}\" registered successfully.");
    }

    public function edit(TelegramGroup $telegramGroup): Response
    {
        return Inertia::render('telecom/telegram-groups/Edit', [
            'group'       => $telegramGroup->load(['branch', 'department']),
            'branches'    => Branch::where('is_active', true)->orderBy('branch_name')->get(),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'groupTypes'  => self::GROUP_TYPES,
            'categories'  => self::CATEGORIES,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function update(Request $request, TelegramGroup $telegramGroup): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'chat_id'       => 'nullable|string|max:100|unique:telegram_groups,chat_id,' . $telegramGroup->id,
            'username'      => 'nullable|string|max:100',
            'group_type'    => 'required|string|in:' . implode(',', self::GROUP_TYPES),
            'category'      => 'nullable|string|max:100',
            'purpose'       => 'nullable|string|max:255',
            'description'   => 'nullable|string',
            'branch_id'     => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'managed_by'    => 'nullable|string|max:255',
            'status'        => 'required|string|in:' . implode(',', self::STATUSES),
            'invite_link'   => 'nullable|url|max:500',
            'is_public'     => 'boolean',
            'member_count'  => 'nullable|integer|min:0',
            'notes'         => 'nullable|string',
        ]);

        $oldLink = $telegramGroup->invite_link;
        $telegramGroup->update($validated);

        if (!empty($validated['invite_link']) && $validated['invite_link'] !== $oldLink) {
            $this->generateQrCode($telegramGroup);
        }

        return redirect()->route('telecom.telegram-groups.index')
            ->with('success', "Telegram group \"{$telegramGroup->name}\" updated successfully.");
    }

    public function destroy(TelegramGroup $telegramGroup): RedirectResponse
    {
        if ($telegramGroup->qr_code_path && Storage::disk('public')->exists($telegramGroup->qr_code_path)) {
            Storage::disk('public')->delete($telegramGroup->qr_code_path);
        }

        $name = $telegramGroup->name;
        $telegramGroup->delete();

        return redirect()->route('telecom.telegram-groups.index')
            ->with('success', "Telegram group \"{$name}\" deleted.");
    }

    public function generateLink(Request $request, TelegramGroup $telegramGroup): RedirectResponse
    {
        $request->validate([
            'invite_link' => 'required|url|max:500',
        ]);

        $telegramGroup->update([
            'invite_link'       => $request->invite_link,
            'link_generated_at' => now(),
        ]);

        $this->generateQrCode($telegramGroup);

        return back()->with('success', 'Invite link saved and QR code generated.');
    }

    private function generateQrCode(TelegramGroup $group): void
    {
        $url = $group->invite_link ?: $group->telegram_url;
        if (!$url) {
            return;
        }

        $dir      = 'telegram-group-qr';
        $filename = "group_{$group->id}.png";
        $path     = "{$dir}/{$filename}";

        try {
            Storage::disk('public')->makeDirectory($dir);
            $apiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($url);
            $imgData = @file_get_contents($apiUrl);
            if ($imgData) {
                Storage::disk('public')->put($path, $imgData);
                $group->update(['qr_code_path' => $path]);
            }
        } catch (\Throwable $e) {
            // Log or ignore QR generation errors safely
        }
    }
}
