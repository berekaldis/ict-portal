<?php

namespace App\Http\Controllers;

use App\Services\CompanySystemSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySystemSyncController extends Controller
{
    /**
     * Manually trigger Company System synchronization (Restricted by permission).
     */
    public function sync(Request $request, CompanySystemSyncService $syncService): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (!$user || (!$user->can('company_system.sync') && !$user->hasRole('Super Admin') && !$user->hasRole('superadmin') && $user->username !== 'superadmin')) {
            abort(403, 'Unauthorized: You do not have permission to sync with the Company System.');
        }

        $result = $syncService->syncAll(false);

        $deptCount = $result['departments']['total_synced'] ?? 0;
        $branchCount = $result['branches']['active_branches_synced'] ?? 0;
        $userCount = $result['users']['total_processed'] ?? 0;

        $message = "Company System synced: {$deptCount} departments, {$branchCount} active branches, {$userCount} users.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'result' => $result,
            ]);
        }

        return back()->with('success', $message);
    }
}
