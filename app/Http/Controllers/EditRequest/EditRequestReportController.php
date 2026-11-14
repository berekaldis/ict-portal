<?php

namespace App\Http\Controllers\EditRequest;

use App\Http\Controllers\Controller;
use App\Models\EditRequest;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditRequestReportController extends Controller
{
    /**
     * Display the full Admin Reports & Analytics interface matching legacy edit_request.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user->can('edit_request.reports.view'), 403, 'Unauthorized access to Analytics & Reports.');

        $isSuperAdmin = $user->hasRole(['Super Admin', 'superadmin']);
        $userDept = strtolower(trim((string)$user->department));
        $deptScope = null;
        if (!$isSuperAdmin) {
            if (str_contains($userDept, 'finance') || $user->hasRole('Finance Manager')) {
                $deptScope = 'Finance';
            } elseif (str_contains($userDept, 'f&b') || str_contains($userDept, 'fb') || $user->hasRole('F&B Manager')) {
                $deptScope = 'F&B';
            }
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branch = $request->input('branch', 'all');
        $userName = trim((string)$request->input('user_name', ''));
        $targetDept = $deptScope ?: $request->input('target_dept', 'all');
        $actionType = $request->input('action_type', 'all');
        $status = $request->input('status', 'all');
        $viewMode = $request->input('view_mode', 'both');

        // Fetch distinct filter options from database
        $filterOptions = [
            'branches' => EditRequest::whereNotNull('branch')->where('branch', '!=', '')->distinct()->orderBy('branch')->pluck('branch')->values()->toArray(),
            'departments' => $deptScope ? [$deptScope] : ['Finance', 'F&B', 'IT Support', 'HR / Training', 'Operations'],
            'voucherTypes' => EditRequest::whereNotNull('voucher_type')->where('voucher_type', '!=', '')->distinct()->orderBy('voucher_type')->pluck('voucher_type')->values()->toArray(),
            'editTypes' => EditRequest::whereNotNull('edit_type')->where('edit_type', '!=', '')->distinct()->orderBy('edit_type')->pluck('edit_type')->values()->toArray(),
            'users' => EditRequest::whereNotNull('user_name')->where('user_name', '!=', '')->distinct()->orderBy('user_name')->pluck('user_name')->values()->toArray(),
        ];

        // Base Query
        $query = EditRequest::query();

        if ($deptScope) {
            $query->where(function ($q) use ($deptScope) {
                $q->where('target_dept', 'like', "%{$deptScope}%");
                if ($deptScope === 'F&B') {
                    $q->orWhere('action_type', 'On Hand Delete');
                }
            });
        }

        if (!empty($startDate)) {
            $query->whereDate('timestamp', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if (!empty($endDate)) {
            $query->whereDate('timestamp', '<=', Carbon::parse($endDate)->endOfDay());
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch', $branch);
        }

        if (!empty($userName)) {
            $query->where(function ($q) use ($userName) {
                $q->where('user_name', 'like', "%{$userName}%")
                  ->orWhere('store_user', 'like', "%{$userName}%");
            });
        }

        if ($targetDept !== 'all' && !empty($targetDept)) {
            $query->where('target_dept', 'like', "%{$targetDept}%");
        }

        if ($actionType !== 'all' && !empty($actionType)) {
            $query->where('action_type', $actionType);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $records = $query->orderBy('timestamp', 'desc')->get();
        $totalCount = $records->count();

        // 1. Summary KPIs
        $pendingCount = $records->where('status', 'Pending')->count();
        $approvedCount = $records->where('status', 'Approved')->count();
        $rejectedCount = $records->where('status', 'Rejected')->count();
        $doneCount = $records->where('status', 'Done')->count();

        $appCount = $approvedCount + $doneCount;
        $approvalRate = $totalCount > 0 ? round(($appCount / $totalCount) * 100) : 0;
        $avgResolutionTime = '24h';

        // 2. Status Distribution
        $statusDistribution = [
            ['key' => 'Pending', 'label' => 'Pending', 'count' => $pendingCount, 'percent' => $totalCount > 0 ? round(($pendingCount / $totalCount) * 100) : 0, 'color' => '#D97706'],
            ['key' => 'Approved', 'label' => 'Approved', 'count' => $approvedCount, 'percent' => $totalCount > 0 ? round(($approvedCount / $totalCount) * 100) : 0, 'color' => '#059669'],
            ['key' => 'Rejected', 'label' => 'Rejected', 'count' => $rejectedCount, 'percent' => $totalCount > 0 ? round(($rejectedCount / $totalCount) * 100) : 0, 'color' => '#DC2626'],
            ['key' => 'Done', 'label' => 'Done', 'count' => $doneCount, 'percent' => $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0, 'color' => '#2563EB'],
        ];

        // 3. Branch Request Rankings (Strictly Active Branches with >0 requests)
        $branchGroups = $records->groupBy('branch');
        $rankedBranches = [];

        foreach ($branchGroups as $bName => $bRecords) {
            $trimmedName = trim((string)$bName);
            if (empty($trimmedName)) continue;

            $bCount = $bRecords->count();
            if ($bCount <= 0) continue;

            $bEdit = $bRecords->where('action_type', 'Edit')->count();
            $bVoid = $bRecords->where('action_type', 'Void')->count();
            $bOnHand = $bRecords->where('action_type', 'On Hand Delete')->count();

            $rankedBranches[] = [
                'name' => $trimmedName,
                'count' => $bCount,
                'edit' => $bEdit,
                'void' => $bVoid,
                'on_hand' => $bOnHand,
            ];
        }

        usort($rankedBranches, fn ($a, $b) => $b['count'] <=> $a['count']);

        $topBranchCount = !empty($rankedBranches) ? $rankedBranches[0]['count'] : 1;
        foreach ($rankedBranches as $index => &$rb) {
            $rb['rank'] = $index + 1;
            $rb['percent'] = round(($rb['count'] / max(1, $topBranchCount)) * 100);
            $rb['sharePercent'] = round(($rb['count'] / max(1, $totalCount)) * 100, 1);
            $rb['medal'] = match ($index + 1) {
                1 => '🥇 #1',
                2 => '🥈 #2',
                3 => '🥉 #3',
                default => '#' . ($index + 1),
            };
        }
        unset($rb);

        // 4. Departmental / Action Metrics
        $deptMap = [];
        foreach ($records as $r) {
            $rawDept = trim((string)($r->target_dept ?? 'General'));
            $dept = explode('(', $rawDept)[0];
            $dept = trim(explode(',', $dept)[0]);
            if (empty($dept)) $dept = 'General';

            if (!isset($deptMap[$dept])) {
                $deptMap[$dept] = [
                    'department' => $dept,
                    'edit' => 0,
                    'void' => 0,
                    'on_hand' => 0,
                    'total' => 0,
                ];
            }

            $deptMap[$dept]['total']++;
            if ($r->action_type === 'Edit') $deptMap[$dept]['edit']++;
            elseif ($r->action_type === 'Void') $deptMap[$dept]['void']++;
            elseif ($r->action_type === 'On Hand Delete') $deptMap[$dept]['on_hand']++;
        }

        $deptMetrics = array_values($deptMap);
        usort($deptMetrics, fn ($a, $b) => $b['total'] <=> $a['total']);

        // 5. Format detail table requests
        $formattedRequests = $records->map(function ($r) {
            return [
                'id' => $r->id,
                'request_id' => $r->request_id,
                'timestamp' => $r->timestamp ? $r->timestamp->toIso8601String() : null,
                'timestamp_formatted' => $r->timestamp ? $r->timestamp->format('Y-m-d H:i') : '-',
                'date_part' => $r->timestamp ? $r->timestamp->format('Y-m-d') : '-',
                'time_part' => $r->timestamp ? $r->timestamp->format('H:i') : '-',
                'branch' => $r->branch ?: 'Unknown',
                'user_name' => $r->user_name ?: '-',
                'store_user' => $r->store_user ?: null,
                'action_type' => $r->action_type ?: 'Edit',
                'voucher_code' => $r->voucher_code ?: null,
                'voucher_type' => $r->voucher_type ?: null,
                'edit_type' => $r->edit_type ?: null,
                'shift' => $r->shift ?: null,
                'incorrect_date' => $r->incorrect_date ?: null,
                'correct_date' => $r->correct_date ?: null,
                'target_dept' => $r->target_dept ?: '-',
                'reason' => $r->reason ?: '-',
                'status' => $r->status ?: 'Pending',
                'approver' => $r->approver ?: '-',
            ];
        })->toArray();

        return Inertia::render('edit-requests/reports', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch' => $branch,
                'user_name' => $userName,
                'target_dept' => $targetDept,
                'action_type' => $actionType,
                'status' => $status,
                'view_mode' => $viewMode,
            ],
            'filterOptions' => $filterOptions,
            'summary' => [
                'totalRequests' => $totalCount,
                'avgResolutionTime' => $avgResolutionTime,
                'approvalRate' => $approvalRate,
                'pending' => $pendingCount,
                'approved' => $approvedCount,
                'rejected' => $rejectedCount,
                'done' => $doneCount,
            ],
            'statusDistribution' => $statusDistribution,
            'rankedBranches' => $rankedBranches,
            'deptMetrics' => $deptMetrics,
            'requests' => $formattedRequests,
        ]);
    }

    /**
     * Export filtered requests to CSV matching legacy edit_request export format.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user->can('edit_request.reports.view'), 403, 'Unauthorized access to Reports Export.');

        $isSuperAdmin = $user->hasRole(['Super Admin', 'superadmin']);
        $userDept = strtolower(trim((string)$user->department));
        $deptScope = null;
        if (!$isSuperAdmin) {
            if (str_contains($userDept, 'finance') || $user->hasRole('Finance Manager')) {
                $deptScope = 'Finance';
            } elseif (str_contains($userDept, 'f&b') || str_contains($userDept, 'fb') || $user->hasRole('F&B Manager')) {
                $deptScope = 'F&B';
            }
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $branch = $request->input('branch', 'all');
        $userName = trim((string)$request->input('user_name', ''));
        $targetDept = $deptScope ?: $request->input('target_dept', 'all');
        $actionType = $request->input('action_type', 'all');
        $status = $request->input('status', 'all');

        $query = EditRequest::query();

        if ($deptScope) {
            $query->where(function ($q) use ($deptScope) {
                $q->where('target_dept', 'like', "%{$deptScope}%");
                if ($deptScope === 'F&B') {
                    $q->orWhere('action_type', 'On Hand Delete');
                }
            });
        }

        if (!empty($startDate)) {
            $query->whereDate('timestamp', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if (!empty($endDate)) {
            $query->whereDate('timestamp', '<=', Carbon::parse($endDate)->endOfDay());
        }
        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch', $branch);
        }
        if (!empty($userName)) {
            $query->where(function ($q) use ($userName) {
                $q->where('user_name', 'like', "%{$userName}%")
                  ->orWhere('store_user', 'like', "%{$userName}%");
            });
        }
        if ($targetDept !== 'all' && !empty($targetDept)) {
            $query->where('target_dept', 'like', "%{$targetDept}%");
        }
        if ($actionType !== 'all' && !empty($actionType)) {
            $query->where('action_type', $actionType);
        }
        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        $records = $query->orderBy('timestamp', 'desc')->get();

        $filename = 'kaldis_requests_report_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Headers
            fputcsv($handle, [
                'ID',
                'Timestamp',
                'Branch',
                'User Name',
                'Store User',
                'Action Type',
                'Voucher Code',
                'Voucher Type',
                'Edit Type',
                'Shift',
                'Incorrect Date',
                'Correct Date',
                'Target Dept',
                'Status',
                'Approver',
                'Reason',
            ]);

            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->request_id,
                    $r->timestamp ? $r->timestamp->format('Y-m-d H:i:s') : '',
                    $r->branch,
                    $r->user_name,
                    $r->store_user ?? '',
                    $r->action_type,
                    $r->voucher_code ?? '',
                    $r->voucher_type ?? '',
                    $r->edit_type ?? '',
                    $r->shift ?? '',
                    $r->incorrect_date ?? '',
                    $r->correct_date ?? '',
                    $r->target_dept ?? '',
                    $r->status,
                    $r->approver ?? '',
                    $r->reason ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Send Weekly or Monthly comprehensive summary report to Telegram matching legacy edit_request format.
     */
    public function sendTelegramReport(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('edit_request.reports.view'), 403, 'Unauthorized access to Telegram Reports.');

        $type = $request->input('type', 'Weekly'); // 'Weekly' or 'Monthly'
        $days = ($type === 'Monthly') ? 30 : 7;

        $records = EditRequest::where('timestamp', '>=', now()->subDays($days))->get();
        $total = $records->count();

        $pending = $records->where('status', 'Pending')->count();
        $approved = $records->where('status', 'Approved')->count();
        $rejected = $records->where('status', 'Rejected')->count();
        $done = $records->where('status', 'Done')->count();

        $actionTotals = [
            'Edit' => $records->where('action_type', 'Edit')->count(),
            'Void' => $records->where('action_type', 'Void')->count(),
            'On Hand Delete' => $records->where('action_type', 'On Hand Delete')->count(),
        ];

        $approvalRate = $total > 0 ? round((($approved + $done) / $total) * 100) : 0;
        $avgRes = '24h';

        // Branch rankings
        $branchCounts = [];
        $editTypeCounts = [];

        foreach ($records as $r) {
            $b = trim((string)($r->branch ?? 'Unknown'));
            if ($b !== '') {
                $branchCounts[$b] = ($branchCounts[$b] ?? 0) + 1;
            }

            if ($r->action_type === 'Edit' && !empty($r->edit_type)) {
                $et = $r->edit_type;
                $editTypeCounts[$et] = ($editTypeCounts[$et] ?? 0) + 1;
            }
        }

        arsort($branchCounts);
        arsort($editTypeCounts);

        $rankedBranchesList = [];
        $rankIdx = 1;
        foreach ($branchCounts as $bName => $bCount) {
            if ($bCount <= 0) continue;
            $medal = match ($rankIdx) {
                1 => '🥇 ',
                2 => '🥈 ',
                3 => '🥉 ',
                default => "{$rankIdx}. ",
            };
            $rankedBranchesList[] = "{$medal}<b>" . TelegramService::escape($bName) . "</b>: {$bCount} req";
            $rankIdx++;
            if ($rankIdx > 10) break; // Top 10 in Telegram
        }
        $rankedBranchesStr = !empty($rankedBranchesList) ? implode("\n", $rankedBranchesList) : "None";

        $topEditTypes = array_slice($editTypeCounts, 0, 5, true);
        $topEditTypesList = [];
        foreach ($topEditTypes as $etName => $etCount) {
            $topEditTypesList[] = TelegramService::escape($etName) . " ({$etCount})";
        }
        $topEditTypesStr = !empty($topEditTypesList) ? implode(', ', $topEditTypesList) : 'None';

        $reportTitle = strtoupper($type) . ' DETAILED REPORT';
        $periodText = $type === 'Weekly' ? 'Last 7 days' : 'Last 30 days';
        $dashboardUrl = url('/edit-requests/reports');

        $message = "<b>" . TelegramService::escape($reportTitle) . "</b>\n\n" .
            "<b>Period:</b> {$periodText}\n" .
            "<b>Total Requests:</b> " . (int)$total . "\n" .
            "<b>Avg. Resolution:</b> " . TelegramService::escape($avgRes) . "\n" .
            "<b>Approval Rate:</b> " . (int)$approvalRate . "%\n\n" .
            "<b>Approved:</b> " . (int)$approved . "\n" .
            "<b>Pending:</b> " . (int)$pending . "\n" .
            "<b>Rejected:</b> " . (int)$rejected . "\n" .
            "<b>Done:</b> " . (int)$done . "\n\n" .
            "🏆 <b>Branch Rankings (Active Branches):</b>\n" . $rankedBranchesStr . "\n\n" .
            "<b>Top Edit Types:</b>\n" . $topEditTypesStr . "\n\n" .
            "<b>Action Mix:</b> Edit " . (int)$actionTotals['Edit'] . " | Void " . (int)$actionTotals['Void'] . " | On Hand Delete " . (int)$actionTotals['On Hand Delete'] . "\n\n" .
            "<a href=\"" . htmlspecialchars($dashboardUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">View Reports Dashboard</a>";

        $chatId = env('TELEGRAM_CHAT_ID_ADMIN', '-1001502645326');
        TelegramService::sendMessage($chatId, $message);

        return back()->with('success', "{$type} summary report successfully dispatched to Telegram.");
    }
}
