<?php

namespace App\Http\Controllers;

use App\Models\PasswordAccessLog;
use App\Models\PasswordEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PasswordManagerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $category = $request->query('category', 'all');
        $branch = $request->query('branch', 'all');
        $authType = $request->query('auth_type', 'all');
        $environment = $request->query('environment', 'all');
        $status = $request->query('status', 'active');

        $query = PasswordEntry::query()
            ->with(['creator:id,name', 'updater:id,name', 'revealer:id,name'])
            ->orderBy('title', 'asc');

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'archived') {
            $query->where('is_active', false);
        }

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch_name', $branch);
        }

        if ($authType !== 'all' && !empty($authType)) {
            $query->where('auth_type', $authType);
        }

        if ($environment !== 'all' && !empty($environment)) {
            $query->where('environment', $environment);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('device_type', 'LIKE', "%{$search}%")
                    ->orWhere('ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('link_url', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('branch_name', 'LIKE', "%{$search}%")
                    ->orWhere('notes', 'LIKE', "%{$search}%");
            });
        }

        $entries = $query->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => PasswordEntry::where('is_active', true)->count(),
            'network_count' => PasswordEntry::where('is_active', true)
                ->whereIn('category', ['Router & Switch', 'Firewall & Security', 'Wi-Fi Access Point'])
                ->count(),
            'cctv_count' => PasswordEntry::where('is_active', true)
                ->where('category', 'CCTV & NVR')
                ->count(),
            'server_count' => PasswordEntry::where('is_active', true)
                ->whereIn('category', ['Server & VM', 'ERP & Database'])
                ->count(),
            'cloud_count' => PasswordEntry::where('is_active', true)
                ->whereIn('category', ['Web & Cloud Service', 'Email & Communication'])
                ->count(),
        ];

        // Branches list
        $branches = [];
        try {
            $dbBranches = DB::table('branches')->pluck('branch_name')->toArray();
            $branches = array_unique(array_merge(['Head Office', 'Data Center', 'Cloud / Hosting'], $dbBranches));
        } catch (\Throwable $e) {
            $branches = ['Head Office', 'Data Center', 'Cloud / Hosting'];
        }

        // Available standard categories
        $categories = [
            'Router & Switch',
            'CCTV & NVR',
            'Server & VM',
            'Firewall & Security',
            'Wi-Fi Access Point',
            'ERP & Database',
            'Web & Cloud Service',
            'Email & Communication',
            'Workstation & PC',
            'Printer & Peripheral',
            'Other',
        ];

        $authTypes = [
            'Password',
            'SSH Key',
            '2FA / OTP',
            'API Token / Key',
            'PIN / Passcode',
            'OAuth / SSO',
            'None',
        ];

        $protocols = [
            'HTTPS',
            'HTTP',
            'SSH',
            'Winbox',
            'RDP',
            'Telnet',
            'MySQL/DB',
            'FTP/SFTP',
            'API',
            'Other',
        ];

        // Recent Audit Logs
        $recentLogs = PasswordAccessLog::with(['user:id,name', 'entry:id,title,category'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return Inertia::render('admin/password-manager/index', [
            'entries' => $entries,
            'stats' => $stats,
            'branches' => array_values($branches),
            'categories' => $categories,
            'authTypes' => $authTypes,
            'protocols' => $protocols,
            'recentLogs' => $recentLogs,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'branch' => $branch,
                'auth_type' => $authType,
                'environment' => $environment,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'device_type' => 'nullable|string|max:100',
            'ip_address' => 'nullable|string|max:64',
            'port' => 'nullable|integer|min:1|max:65535',
            'link_url' => 'nullable|string|max:500',
            'protocol' => 'nullable|string|max:50',
            'username' => 'nullable|string|max:150',
            'password' => 'required|string',
            'secondary_secret' => 'nullable|string',
            'auth_type' => 'required|string|max:50',
            'branch_name' => 'nullable|string|max:150',
            'environment' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $entry = PasswordEntry::create($validated);

        PasswordAccessLog::create([
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return redirect()->back()->with('success', "Credential '{$entry->title}' registered successfully.");
    }

    public function update(Request $request, $id)
    {
        $entry = PasswordEntry::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'device_type' => 'nullable|string|max:100',
            'ip_address' => 'nullable|string|max:64',
            'port' => 'nullable|integer|min:1|max:65535',
            'link_url' => 'nullable|string|max:500',
            'protocol' => 'nullable|string|max:50',
            'username' => 'nullable|string|max:150',
            'password' => 'nullable|string',
            'secondary_secret' => 'nullable|string',
            'auth_type' => 'required|string|max:50',
            'branch_name' => 'nullable|string|max:150',
            'environment' => 'required|string|max:50',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();

        // If password is not provided or empty, do not overwrite the existing encrypted password
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // If secondary_secret is not provided or empty, check if we keep or clear
        if (array_key_exists('secondary_secret', $validated) && $validated['secondary_secret'] === null) {
            $validated['secondary_secret'] = null;
        }

        $entry->update($validated);

        PasswordAccessLog::create([
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return redirect()->back()->with('success', "Credential '{$entry->title}' updated successfully.");
    }

    public function destroy(Request $request, $id)
    {
        $entry = PasswordEntry::findOrFail($id);
        $title = $entry->title;

        PasswordAccessLog::create([
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => 'deleted',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $entry->delete();

        return redirect()->back()->with('success', "Credential '{$title}' removed from password manager.");
    }

    /**
     * Reveal password and secondary secret on-demand with audit logging.
     */
    public function reveal(Request $request, $id)
    {
        $entry = PasswordEntry::findOrFail($id);

        $action = $request->input('action', 'revealed'); // 'revealed' or 'copied'

        // Log reveal/copy event
        PasswordAccessLog::create([
            'entry_id' => $entry->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        $entry->update([
            'last_revealed_at' => now(),
            'last_revealed_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'id' => $entry->id,
            'password' => $entry->getDecryptedPassword(),
            'secondary_secret' => $entry->getDecryptedSecondarySecret(),
        ]);
    }

    /**
     * Export credentials to CSV.
     */
    public function export(Request $request)
    {
        $search = $request->query('search', '');
        $category = $request->query('category', 'all');
        $branch = $request->query('branch', 'all');

        $query = PasswordEntry::query()->where('is_active', true);

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch_name', $branch);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('device_type', 'LIKE', "%{$search}%")
                    ->orWhere('ip_address', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        $entries = $query->orderBy('category')->orderBy('title')->get();

        $canReveal = auth()->user()->can('password_manager.reveal');

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="kaldis_credentials_vault_' . date('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($entries, $canReveal) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, [
                'ID',
                'Title / Device Name',
                'Category',
                'Device Type',
                'IP Address',
                'Port',
                'Link / URL',
                'Protocol',
                'Username / Login',
                'Password',
                'Auth Type',
                'Branch / Location',
                'Environment',
                'Notes',
                'Created At',
            ]);

            foreach ($entries as $e) {
                fputcsv($file, [
                    $e->id,
                    $e->title,
                    $e->category,
                    $e->device_type,
                    $e->ip_address,
                    $e->port,
                    $e->link_url,
                    $e->protocol,
                    $e->username,
                    $canReveal ? $e->getDecryptedPassword() : '••••••••',
                    $e->auth_type,
                    $e->branch_name,
                    $e->environment,
                    $e->notes,
                    $e->created_at ? $e->created_at->format('Y-m-d H:i') : '',
                ]);
            }

            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
