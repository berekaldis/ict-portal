<?php

namespace Tests\Feature;

use App\Models\CaseItem;
use App\Models\EditRequest;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnifiedAuthRbacTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_view_login_screen_for_account_switching(): void
    {
        $branchUser = User::where('username', 'branch_user')->first();
        if (!$branchUser) {
            $branchUser = User::where('role', 'Branch User')->first();
        }
        $response = $this->actingAs($branchUser)->get('/login');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('auth/login')->has('currentUser'));
    }

    public function test_it_team_member_can_access_cases(): void
    {
        $itUser = User::where('username', 'it_admin')->first();
        if (!$itUser) {
            $itUser = User::where('role', 'IT Admin')->first();
        }
        $response = $this->actingAs($itUser)->get('/cases');
        $response->assertStatus(200);
    }

    public function test_finance_manager_is_forbidden_from_case_module(): void
    {
        $financeUser = User::where('username', 'finance_admin')->first();
        if (!$financeUser) {
            $financeUser = User::where('department', 'Finance')->first();
        }
        $response = $this->actingAs($financeUser)->get('/cases');
        $response->assertStatus(403);
    }

    public function test_branch_user_is_forbidden_from_case_module(): void
    {
        $branchUser = User::where('username', 'branch_user')->first();
        if (!$branchUser) {
            $branchUser = User::where('role', 'Branch User')->first();
        }
        $response = $this->actingAs($branchUser)->get('/cases');
        $response->assertStatus(403);
    }

    public function test_finance_manager_can_access_edit_requests(): void
    {
        $financeUser = User::where('username', 'finance_admin')->first();
        if (!$financeUser) {
            $financeUser = User::where('department', 'Finance')->first();
        }
        $response = $this->actingAs($financeUser)->get('/edit-requests');
        $response->assertStatus(200);
    }

    public function test_super_admin_can_access_all_modules(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();

        $this->actingAs($superadmin)->get('/cases')->assertStatus(200);
        $this->actingAs($superadmin)->get('/edit-requests')->assertStatus(200);
        $this->actingAs($superadmin)->get('/users')->assertStatus(200);
        $this->actingAs($superadmin)->get('/roles')->assertStatus(200);
        $this->actingAs($superadmin)->get('/permissions')->assertStatus(200);
    }

    public function test_department_admin_is_strictly_forbidden_from_case_module(): void
    {
        $admin = User::where('username', 'admin')->first();
        if ($admin) {
            $this->actingAs($admin)->get('/cases')->assertStatus(403);
        }

        $financeAdmin = User::where('username', 'finance_admin')->first();
        if ($financeAdmin) {
            $this->actingAs($financeAdmin)->get('/cases')->assertStatus(403);
        }

        $fbAdmin = User::where('username', 'fb_admin')->first();
        if ($fbAdmin) {
            $this->actingAs($fbAdmin)->get('/cases')->assertStatus(403);
        }
    }

    public function test_finance_admin_department_scoping(): void
    {
        $financeAdmin = User::where('username', 'finance_admin')->first();
        if (!$financeAdmin) {
            $financeAdmin = User::where('department', 'Finance')->first();
        }

        // Can view edit requests
        $this->actingAs($financeAdmin)->get('/edit-requests')->assertStatus(200);

        // Can access Finance request
        $finReq = EditRequest::where('target_dept', 'Finance')->first();
        if ($finReq) {
            $this->actingAs($financeAdmin)->get("/edit-requests/{$finReq->id}")->assertStatus(200);
        }

        // Forbidden from F&B request
        $fbReq = EditRequest::where('target_dept', 'F&B')->first();
        if ($fbReq) {
            $this->actingAs($financeAdmin)->get("/edit-requests/{$fbReq->id}")->assertStatus(403);
            $this->actingAs($financeAdmin)->put("/edit-requests/{$fbReq->id}/status", [
                'status' => 'Approved',
            ])->assertStatus(403);
        }
    }

    public function test_fb_admin_department_scoping(): void
    {
        $fbAdmin = User::where('username', 'fb_admin')->first();
        if (!$fbAdmin) {
            $fbAdmin = User::where('department', 'F&B')->first();
        }

        // Can view edit requests
        $this->actingAs($fbAdmin)->get('/edit-requests')->assertStatus(200);

        // Can access F&B request
        $fbReq = EditRequest::where('target_dept', 'F&B')->first();
        if ($fbReq) {
            $this->actingAs($fbAdmin)->get("/edit-requests/{$fbReq->id}")->assertStatus(200);
        }

        // Forbidden from Finance request
        $finReq = EditRequest::where('target_dept', 'Finance')->first();
        if ($finReq) {
            $this->actingAs($fbAdmin)->get("/edit-requests/{$finReq->id}")->assertStatus(403);
            $this->actingAs($fbAdmin)->put("/edit-requests/{$finReq->id}/status", [
                'status' => 'Approved',
            ])->assertStatus(403);
        }
    }

    public function test_it_team_can_create_case(): void
    {
        $itUser = User::where('username', 'it_admin')->first();
        if (!$itUser) {
            $itUser = User::where('role', 'IT Admin')->first();
        }

        $response = $this->actingAs($itUser)->post('/cases', [
            'case_type' => 'Support',
            'description' => 'Automated test support case for CNET POS error',
            'priority' => 'Critical',
            'due_date' => now()->addDays(2)->toDateString(),
            'assigned_to' => 'IT Support Team',
            'remarks' => 'Automated test suite initialization',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('case_cases', [
            'description' => 'Automated test support case for CNET POS error',
            'priority' => 'Critical',
        ]);
    }

    public function test_branch_user_can_submit_edit_request(): void
    {
        $branchUser = User::where('username', 'branch_user')->first();
        if (!$branchUser) {
            $branchUser = User::where('role', 'Branch User')->first();
        }

        $branchName = $branchUser->branch ?: 'Bole';

        $response = $this->actingAs($branchUser)->post('/edit-requests', [
            'action_type' => 'Edit',
            'branch' => $branchName,
            'user_name' => $branchUser->name,
            'target_dept' => 'Finance',
            'voucher_code' => 'VCH-AUTO-TEST-99',
            'voucher_type' => 'Sales Voucher',
            'edit_type' => 'Date Change',
            'reason' => 'Automated test correction request',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('edit_requests', [
            'voucher_code' => 'VCH-AUTO-TEST-99',
        ]);
    }

    public function test_workflow_approval_and_mark_done(): void
    {
        $req = EditRequest::where('voucher_code', 'VCH-AUTO-TEST-99')->first();
        $this->assertNotNull($req);

        // 1. Finance Admin Approves
        $financeUser = User::where('username', 'finance_admin')->first();
        if (!$financeUser) {
            $financeUser = User::where('department', 'Finance')->first();
        }
        $this->actingAs($financeUser)->put("/edit-requests/{$req->id}/status", [
            'status' => 'Approved',
        ])->assertRedirect();

        $req->refresh();
        $this->assertEquals('Approved', $req->status);

        // 2. IT Admin marks Done
        $itUser = User::where('username', 'it_admin')->first();
        if (!$itUser) {
            $itUser = User::where('role', 'IT Admin')->first();
        }
        $this->actingAs($itUser)->put("/edit-requests/{$req->id}/mark-done")->assertRedirect();

        $req->refresh();
        $this->assertEquals('Done', $req->status);
    }

    public function test_single_unified_login_by_username(): void
    {
        $usernames = ['superadmin', 'admin', 'finance_admin', 'fb_admin', 'it_admin', 'branch_user'];
        foreach ($usernames as $u) {
            $response = $this->post('/login', [
                'login' => $u,
                'password' => 'admin123',
            ]);

            $response->assertRedirect('/dashboard');
            $this->assertAuthenticated();
            $this->assertEquals($u, auth()->user()->username);
            auth()->logout();
        }
    }

    public function test_single_login_with_company_system_synced_user(): void
    {
        $response = $this->post('/login', [
            'login' => 'FatumaMsg@kaldisbunna.et',
            'password' => 'admin123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->hasRole('Branch User'));
        $this->assertEquals('4 kilo', auth()->user()->branch);
        auth()->logout();
    }

    public function test_company_system_automated_sync_command(): void
    {
        $exitCode = Artisan::call('sync:company-system', ['--no-cleanup' => true]);
        $this->assertEquals(0, $exitCode);
    }

    public function test_password_update_and_dismiss(): void
    {
        $user = User::where('username', 'superadmin')->first();

        $this->actingAs($user)->post('/password/update', [
            'current_password' => 'admin123',
            'password' => 'newSecretPass123',
            'password_confirmation' => 'newSecretPass123',
        ])->assertRedirect();

        $user->refresh();
        $this->assertFalse((bool)$user->must_change_password);

        $this->actingAs($user)->post('/password/dismiss')->assertRedirect();
    }

    public function test_admin_reports_and_analytics_page(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/edit-requests/reports');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('edit-requests/reports')
            ->has('summary')
            ->has('statusDistribution')
            ->has('rankedBranches')
            ->has('deptMetrics')
            ->has('requests')
            ->has('filterOptions')
        );
    }

    public function test_reports_csv_export(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/edit-requests/reports/export-csv');
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_reports_telegram_dispatch(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/edit-requests/reports/telegram', [
            'type' => 'Weekly',
        ]);
        $response->assertRedirect();
    }

    public function test_manual_sync_restricted_by_permission(): void
    {
        $branchUser = User::where('username', 'branch_user')->first();
        if (!$branchUser) {
            $branchUser = User::where('role', 'Branch User')->first();
        }
        $this->actingAs($branchUser)->post('/company-system/sync')->assertForbidden();

        $superadmin = User::where('username', 'superadmin')->first();
        $this->actingAs($superadmin)->post('/company-system/sync')->assertRedirect();

        // Grant permission to a regular user and test
        $branchUser->givePermissionTo('company_system.sync');
        $this->actingAs($branchUser->fresh())->post('/company-system/sync')->assertRedirect();
        $branchUser->revokePermissionTo('company_system.sync');
    }

    public function test_single_user_direct_permission_management_and_safe_sync(): void
    {
        $superadmin = User::where('username', 'superadmin')->first();
        $branchUser = User::where('username', 'branch_user')->first();

        // 1. Update single user with direct permissions via UserController
        $this->actingAs($superadmin)->put("/users/{$branchUser->id}", [
            'name' => $branchUser->name,
            'email' => $branchUser->email,
            'department' => $branchUser->department,
            'branch' => $branchUser->branch,
            'chat_id' => $branchUser->chat_id,
            'is_active' => true,
            'roles' => ['Branch User'],
            'permissions' => ['edit_request.reports.view'],
        ])->assertRedirect('/users');

        $branchUser->refresh();
        $this->assertTrue($branchUser->hasDirectPermission('edit_request.reports.view'));
        $this->assertTrue($branchUser->can('edit_request.reports.view'));

        // 2. Safe sync preserves existing user roles, department, and custom direct permissions
        $syncService = app(\App\Services\CompanySystemSyncService::class);
        $syncResult = $syncService->syncAll();

        $this->assertArrayHasKey('departments', $syncResult);
        $this->assertArrayHasKey('branches', $syncResult);
        $this->assertArrayHasKey('users', $syncResult);

        // Verify existing user was preserved with their direct permissions intact
        $branchUser->refresh();
        $this->assertTrue($branchUser->hasDirectPermission('edit_request.reports.view'));

        // Clean up direct permission
        $branchUser->revokePermissionTo('edit_request.reports.view');
    }
}
