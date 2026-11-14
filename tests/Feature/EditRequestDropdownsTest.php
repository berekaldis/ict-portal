<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EditRequestDropdownsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);
    }
    public function test_create_page_contains_active_branches_and_exact_edit_request_dropdowns(): void
    {
        $user = User::where('username', 'bole_branch')->first();
        if (!$user) {
            $user = User::first();
        }

        $response = $this->actingAs($user)->get(route('edit-requests.create'));
        $response->assertStatus(200);

        // Verify active branches from company_system
        $activeBranches = DB::connection('company_system')
            ->table('branches')
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        $response->assertInertia(fn ($page) => $page
            ->component('edit-requests/create')
            ->has('branches')
            ->where('branches', $activeBranches)
            ->where('voucherTypes', [
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
            ])
            ->where('editTypes', [
                'Date Change',
                'Quantity and Price',
                'Quantity Only',
                'Price Only',
                'Supplier Name',
                'Incorrect Store Selection',
                'Incorrect Item Selection',
                'FS Number',
                'Item Only',
            ])
            ->where('targetDepartments', [
                'Finance',
                'F&B',
            ])
            ->where('shifts', [
                'shft 1',
                'shft 2',
            ])
            ->has('storeDepartments')
            ->where('userBranch', $user->branch)
        );
    }

    public function test_submit_on_hand_delete_request_stores_properly(): void
    {
        $user = User::where('username', 'bole_branch')->first();
        if (!$user) {
            $user = User::first();
        }

        $response = $this->actingAs($user)->post(route('edit-requests.store'), [
            'action_type' => 'On Hand Delete',
            'branch' => $user->branch ?: 'Bole',
            'user_name' => 'Bole Requester',
            'store_user' => 'Abebech (Kitchen Store)',
            'target_dept' => 'F&B',
            'store_departments' => ['Hot Kitchen', 'Pastry'],
            'correct_date' => '2026-11-13',
            'shift' => 'shft 1',
            'reason' => 'Inventory on-hand balance adjustment',
        ]);

        $response->assertRedirect(route('edit-requests.index'));
        $this->assertDatabaseHas('edit_requests', [
            'action_type' => 'On Hand Delete',
            'branch' => $user->branch ?: 'Bole',
            'store_user' => 'Abebech (Kitchen Store)',
            'target_dept' => 'F&B (Hot Kitchen, Pastry)',
            'shift' => 'shft 1',
        ]);
    }

    public function test_branch_user_is_enforced_to_own_branch(): void
    {
        $user = User::where('username', 'bole_branch')->first();
        if (!$user) {
            $user = User::whereNotNull('branch')->where('branch', '!=', '')->first() ?? User::first();
        }
        if (!$user->branch) {
            $user->branch = 'Bole';
            $user->save();
        }

        // Even if an attacker attempts to pass another branch via request payload
        $response = $this->actingAs($user)->post(route('edit-requests.store'), [
            'action_type' => 'Edit',
            'branch' => 'Some Other Branch 4 kilo',
            'user_name' => 'Bole Requester',
            'target_dept' => 'Finance',
            'voucher_code' => 'VCH-ATTACK-001',
            'voucher_type' => 'Store Issue Voucher',
            'edit_type' => 'Date Change',
            'incorrect_date' => '2026-11-01',
            'correct_date' => '2026-11-02',
            'reason' => 'Branch lock enforcement test',
        ]);

        $response->assertRedirect(route('edit-requests.index'));
        // Verified it stored with the logged in user's branch ('Bole'), NOT 'Some Other Branch 4 kilo'
        $this->assertDatabaseHas('edit_requests', [
            'voucher_code' => 'VCH-ATTACK-001',
            'branch' => $user->branch ?: 'Bole',
        ]);
    }
}
