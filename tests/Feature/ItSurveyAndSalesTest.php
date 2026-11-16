<?php

namespace Tests\Feature;

use App\Models\ItHardwareAsset;
use App\Models\ItSurveyCategory;
use App\Models\ItSurveyReport;
use App\Models\SalesCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItSurveyAndSalesTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = [
            'it_survey.view',
            'it_survey.create',
            'it_survey.manage',
            'sales_case.view',
            'sales_case.create',
            'sales_case.manage',
            'view dashboard',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        \Illuminate\Support\Facades\Http::fake([
            'api.telegram.org/*' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
            '*' => \Illuminate\Support\Facades\Http::response(['ok' => true], 200),
        ]);

        $this->admin = User::firstOrCreate(
            ['username' => 'test_admin_survey_sales'],
            [
                'name' => 'Survey & Sales Tester',
                'password' => bcrypt('password123'),
                'role' => 'Super Admin',
                'is_active' => true,
            ]
        );
        $this->admin->assignRole('Super Admin');
        $this->admin->givePermissionTo($permissions);
    }

    public function test_can_view_it_surveys_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('it-surveys.index'));
        $response->assertStatus(200);
    }

    public function test_can_view_it_survey_create_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('it-surveys.create'));
        $response->assertStatus(200);
    }

    public function test_can_submit_it_survey_inspection(): void
    {
        $payload = [
            'branch_name' => 'Bole Medhanialem',
            'technician_name' => 'Abebe IT',
            'branch_manager' => 'Manager Kebede',
            'type' => 'hardware',
            'checklist_data' => [
                [
                    'category_id' => 1,
                    'category_name' => 'POS Terminal',
                    'item_id' => 1,
                    'question' => 'Touch screen functional?',
                    'status' => 'OK',
                    'detail' => '',
                    'skip_reason' => '',
                ],
                [
                    'category_id' => 1,
                    'category_name' => 'POS Terminal',
                    'item_id' => 2,
                    'question' => 'Printer cutter operational?',
                    'status' => 'Issue',
                    'detail' => 'Cutter jammed with paper dust',
                    'skip_reason' => '',
                ],
            ],
            'issues_summary' => 'Printer jammed at counter 1',
            'actions_taken' => 'Cleaned roller and test printed OK',
            'recommendations' => 'Replace thermal head if issue persists',
        ];

        $response = $this->actingAs($this->admin)->post(route('it-surveys.store'), $payload);
        $response->assertStatus(302);

        $this->assertDatabaseHas('it_survey_reports', [
            'branch_name' => 'Bole Medhanialem',
            'technician_name' => 'Abebe IT',
            'type' => 'hardware',
            'health_score' => 50,
            'critical_count' => 1,
        ]);
    }

    public function test_can_view_hardware_inventory(): void
    {
        $response = $this->actingAs($this->admin)->get(route('it-surveys.hardware'));
        $response->assertRedirect(route('it-surveys.index', ['tab' => 'hardware']));
    }

    public function test_can_delete_it_survey_report(): void
    {
        $report = ItSurveyReport::create([
            'report_uuid' => (string) \Illuminate\Support\Str::uuid(),
            'branch_name' => 'Delete Branch Test',
            'technician_name' => 'Tester',
            'branch_manager' => 'Mgr Test',
            'type' => 'hardware',
            'health_score' => 100,
            'health_status' => 'Good',
            'critical_count' => 0,
            'total_items' => 1,
            'passed_items' => 1,
            'failed_items' => 0,
            'skipped_items' => 0,
            'checklist_data' => [],
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->delete(route('it-surveys.destroy', $report->id));
        $response->assertRedirect(route('it-surveys.index'));
        $this->assertDatabaseMissing('it_survey_reports', ['id' => $report->id]);
    }

    public function test_can_add_hardware_asset(): void
    {
        $payload = [
            'branch_name' => 'Kazanchis',
            'asset_tag' => 'AST-KZ-TEST-01',
            'serial_number' => 'SN-KZ-9988',
            'asset_type' => 'switch',
            'brand' => 'Cisco',
            'model' => 'SG200-24',
            'status' => 'operational',
            'assigned_to' => 'Server Room',
            'ip_address' => '192.168.20.2',
            'notes' => 'Test network switch',
        ];

        $response = $this->actingAs($this->admin)->post(route('it-surveys.hardware.store'), $payload);
        $response->assertStatus(302);

        $this->assertDatabaseHas('it_hardware_assets', [
            'asset_tag' => 'AST-KZ-TEST-01',
            'serial_number' => 'SN-KZ-9988',
        ]);
    }

    public function test_can_export_it_surveys_and_hardware_csv(): void
    {
        $respReports = $this->actingAs($this->admin)->get(route('it-surveys.export.reports'));
        $respReports->assertStatus(200);
        $this->assertStringContainsString('text/csv', $respReports->headers->get('Content-Type'));

        $respHw = $this->actingAs($this->admin)->get(route('it-surveys.export.hardware'));
        $respHw->assertStatus(200);
        $this->assertStringContainsString('text/csv', $respHw->headers->get('Content-Type'));
    }

    public function test_can_view_sales_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('sales.dashboard'));
        $response->assertStatus(200);
    }

    public function test_can_view_sales_cases_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('sales.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_sales_discrepancy_case(): void
    {
        $payload = [
            'date' => '2026-11-14',
            'branch_name' => 'Bole Medhanialem',
            'z_report' => 25000.50,
            'cash_sales' => 24500.00,
            'progress' => 'In Progress',
            'technical_name' => 'Dawit IT',
            'remark' => '500.50 ETB discrepancy on cashier #2',
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);
        $response->assertStatus(302);

        $this->assertDatabaseHas('sales_cases', [
            'branch_name' => 'Bole Medhanialem',
            'z_report' => 25000.50,
            'cash_sales' => 24500.00,
            'difference' => 500.50,
            'progress' => 'In Progress',
        ]);
    }

    public function test_can_update_sales_case_progress_and_approve(): void
    {
        $uniqueId = 'CASE-TEST-' . uniqid();
        $case = SalesCase::create([
            'case_id' => $uniqueId,
            'date' => '2026-11-14',
            'branch_name' => 'Kazanchis',
            'z_report' => 10000,
            'cash_sales' => 10000,
            'difference' => 0,
            'progress' => 'Not Start',
        ]);

        // Status update
        $resProg = $this->actingAs($this->admin)->post(route('sales.update-progress', $case->id), [
            'progress' => 'Done',
            'note' => 'Discrepancy resolved after receipt check',
        ]);
        $resProg->assertStatus(302);
        $this->assertEquals('Done', $case->fresh()->progress);

        // Approve
        $resApprove = $this->actingAs($this->admin)->post(route('sales.approve', $case->id));
        $resApprove->assertStatus(302);
        $this->assertTrue($case->fresh()->approved);

        // Add Comment
        $resComment = $this->actingAs($this->admin)->post(route('sales.comment', $case->id), [
            'comment' => 'Verified with bank deposit slip.',
        ]);
        $resComment->assertStatus(302);
        $this->assertDatabaseHas('sales_case_comments', [
            'case_id' => $uniqueId,
            'comment' => 'Verified with bank deposit slip.',
        ]);
    }
}
