<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\HrSetting;
use App\Services\CompanySystemSyncService;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BranchManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        $query = Branch::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('telegram_chat_id', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $branches = $query->orderBy('branch_name')->paginate(30)->withQueryString();

        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('is_active', true)->count(),
            'with_telegram' => Branch::whereNotNull('telegram_chat_id')->where('telegram_chat_id', '!=', '')->count(),
        ];

        return Inertia::render('admin/branches/index', [
            'branches' => $branches,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_name' => 'required|string|max:150|unique:branches,branch_name',
            'telegram_chat_id' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $branch = Branch::create([
            'branch_name' => trim($validated['branch_name']),
            'telegram_chat_id' => !empty($validated['telegram_chat_id']) ? trim($validated['telegram_chat_id']) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Keep hr_settings branch list synchronized
        HrSetting::updateOrCreate(
            ['type' => 'branch', 'value' => $branch->branch_name],
            ['chat_id' => $branch->telegram_chat_id]
        );

        return back()->with('success', "Branch '{$branch->branch_name}' created successfully.");
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'branch_name' => 'required|string|max:150|unique:branches,branch_name,' . $branch->id,
            'telegram_chat_id' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $oldName = $branch->branch_name;

        $branch->update([
            'branch_name' => trim($validated['branch_name']),
            'telegram_chat_id' => !empty($validated['telegram_chat_id']) ? trim($validated['telegram_chat_id']) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Keep hr_settings branch updated
        $hrSetting = HrSetting::where('type', 'branch')->where('value', $oldName)->first();
        if ($hrSetting) {
            $hrSetting->update([
                'value' => $branch->branch_name,
                'chat_id' => $branch->telegram_chat_id,
            ]);
        } else {
            HrSetting::updateOrCreate(
                ['type' => 'branch', 'value' => $branch->branch_name],
                ['chat_id' => $branch->telegram_chat_id]
            );
        }

        return back()->with('success', "Branch '{$branch->branch_name}' updated successfully.");
    }

    public function destroy($id): RedirectResponse
    {
        $branch = Branch::findOrFail($id);
        $name = $branch->branch_name;

        HrSetting::where('type', 'branch')->where('value', $name)->delete();
        $branch->delete();

        return back()->with('success', "Branch '{$name}' deleted successfully.");
    }

    public function sync(): RedirectResponse
    {
        try {
            $syncService = app(CompanySystemSyncService::class);
            $res = $syncService->syncBranches();

            $count = $res['active_branches_synced'] ?? 0;
            return back()->with('success', "Successfully synchronized {$count} active branches from Company System.");
        } catch (\Throwable $e) {
            return back()->with('error', "Failed to sync branches: " . $e->getMessage());
        }
    }
}
