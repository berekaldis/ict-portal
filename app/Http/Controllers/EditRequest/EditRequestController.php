<?php

namespace App\Http\Controllers\EditRequest;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EditRequest;
use App\Models\EditRequestUsername;
use App\Models\EditSystemLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EditRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = EditRequest::query();

        // Branch-level scoping for standard Branch Users without view.all permission
        if ($user->hasRole(['Branch User', 'branch user', 'branch_user']) && !$user->can('edit_request.view.all') && !empty($user->branch)) {
            $query->where('branch', $user->branch);
        }

        // Department-level scoping: Finance managers only manage Finance; F&B managers only manage F&B
        $deptScope = $this->getUserDepartmentScope($user);
        if ($deptScope && in_array($deptScope, ['Finance', 'F&B']) && !$user->hasRole(['Super Admin', 'superadmin'])) {
            $query->where(function ($q) use ($deptScope) {
                $q->where('target_dept', 'like', "%{$deptScope}%");
                if ($deptScope === 'F&B') {
                    $q->orWhere('action_type', 'On Hand Delete');
                }
            });
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_id', 'like', "%{$search}%")
                    ->orWhere('branch', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('voucher_code', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'All') {
                $query->where('status', $status);
            }
        }

        if ($actionType = $request->input('action_type')) {
            if ($actionType !== 'All') {
                $query->where('action_type', $actionType);
            }
        }

        if ($targetDept = $request->input('target_dept')) {
            if ($targetDept !== 'All') {
                $query->where('target_dept', 'like', "%{$targetDept}%");
            }
        }

        if ($branch = $request->input('branch')) {
            if ($branch !== 'All') {
                $query->where('branch', $branch);
            }
        }

        $period = $request->input('period', 'all');
        if ($period === '7') {
            $query->where('timestamp', '>=', now()->subDays(7));
        } elseif ($period === '30') {
            $query->where('timestamp', '>=', now()->subDays(30));
        }

        $requests = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $branches = [];
        try {
            $branches = DB::connection('company_system')
                ->table('branches')
                ->where('status', 'active')
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Company system branch fetch failed: ' . $e->getMessage());
            $branches = [];
        }

        if (empty($branches)) {
            $branches = Branch::where('is_active', true)->pluck('branch_name')->toArray();
            if (empty($branches)) {
                $branches = EditRequest::distinct()->whereNotNull('branch')->pluck('branch')->toArray();
            }
        }

        return Inertia::render('edit-requests/index', [
            'requests' => $requests,
            'filters' => $request->only(['search', 'status', 'action_type', 'target_dept', 'branch', 'period']),
            'branches' => $branches,
        ]);
    }

    public function create(): Response
    {
        $branches = [];
        try {
            $branches = DB::connection('company_system')
                ->table('branches')
                ->where('status', 'active')
                ->orderBy('name')
                ->pluck('name')
                ->toArray();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Company system branch fetch failed: ' . $e->getMessage());
            $branches = [];
        }

        if (empty($branches)) {
            $branches = Branch::where('is_active', true)->pluck('branch_name')->toArray();
            if (empty($branches)) {
                $branches = EditRequest::distinct()->whereNotNull('branch')->pluck('branch')->toArray();
            }
        }

        // Exact Voucher Types from edit_request folder
        $voucherTypes = [
            'Store Issue Voucher',
            'Fresh Bazar Voucher',
            'Item Consumption Voucher',
            'Damage Return Voucher',
            'Cash Good Receiving Voucher',
            'Bank Deposit Voucher',
            'Branch Department Transfer Voucher',
            'Credit Good Receiving Voucher',
            'Internal Store Transfer Voucher',
            'Store Transfer Voucher',
            'Cash Sales Adjustment Voucher',
            'Motivation Voucher',
            'Customer Complaint Voucher',
            'Product Transfer Voucher',
        ];

        // Exact Edit Types from edit_request folder
        $editTypes = [
            'Date Change',
            'Quantity and Price',
            'Quantity Only',
            'Price Only',
            'Supplier Name',
            'Incorrect Store Selection',
            'Incorrect Item Selection',
            'FS Number',
            'Item Only',
        ];

        // Target Departments (IT removed as requested: only Finance and F&B)
        $targetDepartments = [
            'Finance',
            'F&B',
        ];

        // Exact Shifts from edit_request folder
        $shifts = [
            'shft 1',
            'shft 2',
        ];

        // Store Departments from edit_request folder & settings table
        $defaultStoreDepts = [
            "Hot Kitchen",
            "Bottle & Can Bar",
            "Pastry",
            "Food Store",
            "Juice & Smoothie Bar",
            "Steward",
            "Cup Store",
            "Ice Cream & Shake Bar",
            "Coffee Bar",
            "Milk Store",
            "Super Market Store",
            "Staff Canteen",
        ];

        $storeDepartments = $defaultStoreDepts;
        try {
            $settingVal = DB::table('settings')->where('setting_key', 'store_departments')->value('setting_value');
            if ($settingVal) {
                $decoded = json_decode($settingVal, true);
                if (is_array($decoded) && !empty($decoded)) {
                    $storeDepartments = array_values(array_unique(array_filter($decoded)));
                }
            }
        } catch (\Throwable $e) {
            // fallback to default list
        }

        $currentUser = auth()->user();
        $userBranch = $currentUser?->branch;

        return Inertia::render('edit-requests/create', [
            'branches' => $branches,
            'voucherTypes' => $voucherTypes,
            'editTypes' => $editTypes,
            'targetDepartments' => $targetDepartments,
            'shifts' => $shifts,
            'storeDepartments' => $storeDepartments,
            'userBranch' => $userBranch,
            'canChangeBranch' => empty($userBranch) || ($currentUser && $currentUser->hasRole(['Super Admin', 'superadmin'])),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch' => 'nullable|string|max:200',
            'user_name' => 'required|string|max:100',
            'action_type' => 'required|in:Edit,Void,On Hand Delete',
            'target_dept' => 'required|string|max:100',
            'reason' => 'nullable|string',
            'voucher_code' => 'nullable|string|max:255',
            'voucher_codes' => 'nullable|array',
            'voucher_type' => 'nullable|required_unless:action_type,On Hand Delete|string|max:100',
            'edit_type' => 'nullable|string|max:100',
            'store_user' => 'nullable|string|max:150',
            'store_departments' => 'nullable|array',
            'departments' => 'nullable|array',
            'incorrect_date' => 'nullable|string|max:50',
            'correct_date' => 'nullable|string|max:50',
            'shift' => 'nullable|string|max:50',
        ]);

        $currentUser = $request->user();
        $branch = !empty($currentUser?->branch) && !$currentUser->hasRole(['Super Admin', 'superadmin'])
            ? $currentUser->branch
            : ($validated['branch'] ?? $currentUser?->branch ?? '');

        if (empty($branch)) {
            return back()->withErrors(['branch' => 'Branch is required.']);
        }

        $actionType = $validated['action_type'];
        $baseTargetDept = $validated['target_dept'];

        // On Hand Delete specifics (Store User and Department Stores)
        if ($actionType === 'On Hand Delete') {
            if (empty(trim($validated['store_user'] ?? ''))) {
                return back()->withErrors(['store_user' => 'Department Store User is required for On Hand Delete requests.']);
            }

            if (empty($validated['correct_date'])) {
                return back()->withErrors(['correct_date' => 'On Hand Delete Date is required.']);
            }

            $selectedStoreDepts = $request->input('store_departments', $request->input('departments', []));
            if (empty($selectedStoreDepts) || !is_array($selectedStoreDepts)) {
                return back()->withErrors(['store_departments' => 'Please select at least one Department Store.']);
            }

            $cleanedStoreDepts = array_values(array_filter(array_map('trim', $selectedStoreDepts)));
            if (empty($cleanedStoreDepts)) {
                return back()->withErrors(['store_departments' => 'Please select at least one Department Store.']);
            }

            $targetDept = $baseTargetDept . ' (' . implode(', ', $cleanedStoreDepts) . ')';
        } else {
            $targetDept = $baseTargetDept;
        }

        // Voucher code resolution
        $voucherCode = null;
        if ($actionType !== 'On Hand Delete') {
            if (!empty($validated['voucher_codes'])) {
                $voucherCode = implode(', ', array_filter(array_map('trim', $validated['voucher_codes'])));
            }
            if (empty($voucherCode)) {
                $voucherCode = $validated['voucher_code'] ?? null;
            }
            if (empty($voucherCode)) {
                return back()->withErrors(['voucher_code' => 'The voucher code field is required.']);
            }
        }

        $reason = trim($validated['reason'] ?? '');
        if ($actionType === 'On Hand Delete' && empty($reason)) {
            $reason = 'On Hand Delete Request';
        } elseif (empty($reason)) {
            return back()->withErrors(['reason' => 'The reason for request is required.']);
        }

        // Prefix generation: OHD for On Hand Delete, or department prefix (FIN, FB, IT, etc.)
        $prefix = match (true) {
            $actionType === 'On Hand Delete' => 'OHD',
            str_contains(strtolower($targetDept), 'finance') => 'FIN',
            str_contains(strtolower($targetDept), 'f&b') || str_contains(strtolower($targetDept), 'fb') => 'FB',
            str_contains(strtolower($targetDept), 'it') => 'IT',
            default => 'REQ'
        };

        $dateStr = date('Ymd');
        $pattern = "{$prefix}-{$dateStr}-";
        $last = EditRequest::where('request_id', 'like', "{$pattern}%")->orderByDesc('id')->first();
        $nextNum = 1;
        if ($last && preg_match('/-(\d+)$/', $last->request_id, $matches)) {
            $nextNum = (int)$matches[1] + 1;
        }
        $requestId = $pattern . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

        $editRequest = EditRequest::create([
            'request_id' => $requestId,
            'timestamp' => now(),
            'branch' => $branch,
            'user_name' => $validated['user_name'],
            'store_user' => $validated['store_user'] ?? null,
            'voucher_code' => $voucherCode,
            'voucher_type' => $actionType === 'On Hand Delete' ? null : ($validated['voucher_type'] ?? null),
            'action_type' => $actionType,
            'edit_type' => $actionType === 'On Hand Delete' ? null : ($validated['edit_type'] ?? null),
            'reason' => $reason,
            'target_dept' => $targetDept,
            'incorrect_date' => $actionType === 'On Hand Delete' ? null : ($validated['incorrect_date'] ?? null),
            'correct_date' => $validated['correct_date'] ?? null,
            'shift' => $validated['shift'] ?? 'shft 1',
            'status' => 'Pending',
            'approver' => null,
            'telegram_msg_id' => null,
        ]);

        // Track username
        EditRequestUsername::track($validated['user_name'], $branch);

        // Log action
        $actor = $request->user()->name ?: $request->user()->username;
        EditSystemLog::log('Created Request', $actor, $requestId);

        // Notify Telegram
        TelegramService::notifyEditRequest($editRequest->toArray(), 'NEW_REQUEST', $actor);

        return redirect()->route('edit-requests.index')->with('success', "Request {$requestId} submitted successfully!");
    }

    public function show(int $id): Response
    {
        $request = EditRequest::findOrFail($id);
        $user = auth()->user();

        if ($user && !$this->canUserAccessRequest($user, $request)) {
            $deptScope = $this->getUserDepartmentScope($user);
            abort(403, "Access restricted to {$deptScope} department managers only.");
        }

        $logs = EditSystemLog::where('request_id', $request->request_id)->orderByDesc('timestamp')->get();

        return Inertia::render('edit-requests/show', [
            'requestItem' => $request,
            'logs' => $logs,
        ]);
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $editRequest = EditRequest::findOrFail($id);
        $user = $request->user();

        if (!$this->canUserAccessRequest($user, $editRequest)) {
            $deptScope = $this->getUserDepartmentScope($user);
            abort(403, "You can only approve or reject requests for the {$deptScope} department.");
        }

        $validated = $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'reason' => 'nullable|string',
        ]);

        $actor = $request->user()->name ?: $request->user()->username;
        $editRequest->status = $validated['status'];
        $editRequest->approver = $actor;
        $editRequest->save();

        EditSystemLog::log("Status changed to {$validated['status']}", $actor, $editRequest->request_id);

        TelegramService::notifyEditRequest($editRequest->toArray(), $validated['status'], $actor);

        return back()->with('success', "Request {$editRequest->request_id} has been {$validated['status']}.");
    }

    public function markDone(Request $request, int $id): RedirectResponse
    {
        $editRequest = EditRequest::findOrFail($id);
        $actor = $request->user()->name ?: $request->user()->username;

        $editRequest->status = 'Done';
        $editRequest->save();

        EditSystemLog::log('Marked as Done by IT', $actor, $editRequest->request_id);

        TelegramService::notifyEditRequest($editRequest->toArray(), 'Done', $actor);

        return back()->with('success', "Request {$editRequest->request_id} marked as Done.");
    }

    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'status' => 'required|in:Approved,Rejected,Done',
        ]);

        $user = $request->user();
        $actor = $user->name ?: $user->username;
        $items = EditRequest::whereIn('id', $validated['ids'])->get();

        // Enforce department access filtering
        $authorizedItems = $items->filter(fn ($item) => $this->canUserAccessRequest($user, $item));

        if ($authorizedItems->isEmpty()) {
            return back()->withErrors(['ids' => 'No authorized requests found for your department.']);
        }

        foreach ($authorizedItems as $item) {
            $item->status = $validated['status'];
            if ($validated['status'] === 'Approved' || $validated['status'] === 'Rejected') {
                $item->approver = $actor;
            }
            $item->save();

            EditSystemLog::log("Bulk updated to {$validated['status']}", $actor, $item->request_id);
            TelegramService::notifyEditRequest($item->toArray(), $validated['status'], $actor);
        }

        return back()->with('success', count($authorizedItems) . " requests marked as {$validated['status']}.");
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $item = EditRequest::findOrFail($id);
        $user = $request->user();

        if (!$this->canUserAccessRequest($user, $item)) {
            $deptScope = $this->getUserDepartmentScope($user);
            abort(403, "You can only delete requests for the {$deptScope} department.");
        }

        $reqId = $item->request_id;
        $actor = $user->name ?: $user->username;

        EditSystemLog::log('Deleted Request', $actor, $reqId);
        $item->delete();

        return redirect()->route('edit-requests.index')->with('success', "Request {$reqId} deleted.");
    }

    /**
     * Get user's department scope.
     * Super Admin has full permission across all departments.
     * Department Admin/Manager is restricted to their assigned department.
     */
    protected function getUserDepartmentScope(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->hasRole(['Super Admin', 'superadmin'])) {
            return null; // Full access across all departments
        }

        $dept = strtolower(trim((string)$user->department));
        if (str_contains($dept, 'finance')) {
            return 'Finance';
        }
        if (str_contains($dept, 'f&b') || str_contains($dept, 'fb') || str_contains($dept, 'beverage') || str_contains($dept, 'food')) {
            return 'F&B';
        }
        if (str_contains($dept, 'it') || str_contains($dept, 'ict')) {
            return 'IT';
        }

        if ($user->hasRole('Finance Manager')) {
            return 'Finance';
        }
        if ($user->hasRole('F&B Manager')) {
            return 'F&B';
        }
        if ($user->hasRole(['IT Admin', 'IT Staff'])) {
            return 'IT';
        }

        return null;
    }

    /**
     * Check if a user can access / manage a specific edit request based on department and role permissions.
     */
    protected function canUserAccessRequest(User $user, EditRequest $requestItem): bool
    {
        if ($user->hasRole(['Super Admin', 'superadmin'])) {
            return true; // Super Admin has full permission
        }

        // Branch User can only access own branch
        if ($user->hasRole(['Branch User', 'branch user']) && !$user->can('edit_request.view.all')) {
            return !empty($user->branch) && strtolower($user->branch) === strtolower($requestItem->branch);
        }

        $deptScope = $this->getUserDepartmentScope($user);
        if ($deptScope && in_array($deptScope, ['Finance', 'F&B'])) {
            $matchesDept = str_contains(strtolower((string)$requestItem->target_dept), strtolower($deptScope))
                || ($deptScope === 'F&B' && $requestItem->action_type === 'On Hand Delete');
            return $matchesDept;
        }

        // IT team can view and execute marked done
        if ($deptScope === 'IT' || $user->hasRole(['IT Admin', 'IT Staff'])) {
            return true;
        }

        return true;
    }
}
