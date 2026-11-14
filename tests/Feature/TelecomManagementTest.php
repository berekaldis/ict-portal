<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Branch;
use App\Models\Department;
use App\Models\TelecomProvider;
use App\Models\TelecomPhoneNumber;
use App\Models\TelecomBroadband;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TelecomManagementTest extends TestCase
{
    use DatabaseTransactions;
    protected User $admin;
    protected TelecomProvider $provider;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('username', 'superadmin')->first() ?? User::firstOrCreate(
            ['username' => 'telecom_test_admin'],
            [
                'name' => 'Telecom Test Admin',
                'email' => 'telecom_test_admin@example.com',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->admin->assignRole($superAdminRole);

        $this->provider = TelecomProvider::firstOrCreate(
            ['code' => 'ETHIO_TEL_TEST'],
            [
                'name' => 'Ethio Telecom Test',
                'support_contact' => '994',
                'is_active' => true,
            ]
        );

        $this->branch = Branch::first() ?? Branch::create([
            'branch_code' => 'TEST_BR_01',
            'branch_name' => 'Test Branch',
            'is_active' => true,
        ]);
    }

    public function test_telecom_dashboard_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.dashboard'));
        $response->assertStatus(200);
    }

    public function test_phone_numbers_index_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.phone-numbers.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_phone_number()
    {
        $num = '+2519' . rand(20000000, 89999999);
        $payload = [
            'phone_number' => $num,
            'account_number' => 'ACC-' . rand(100000, 999999),
            'sim_card_number' => 'SIM-' . rand(100000, 999999),
            'telecom_provider_id' => $this->provider->id,
            'service_type' => 'Mobile',
            'package_type' => 'Unlimited Monthly',
            'package_start_date' => now()->toDateString(),
            'monthly_cost' => 1250.00,
            'billing_type' => 'Postpaid',
            'assigned_type' => 'Branch',
            'branch_id' => $this->branch->id,
            'status' => 'Active',
        ];

        $response = $this->actingAs($this->admin)->post(route('telecom.phone-numbers.store'), $payload);
        $response->assertRedirect(route('telecom.phone-numbers.index'));

        $this->assertDatabaseHas('telecom_phone_numbers', [
            'phone_number' => $num,
        ]);
    }

    public function test_can_transfer_phone_number()
    {
        $num = '+2519' . rand(20000000, 89999999);
        $phone = TelecomPhoneNumber::create([
            'phone_number' => $num,
            'telecom_provider_id' => $this->provider->id,
            'service_type' => 'Mobile',
            'assigned_type' => 'Branch',
            'branch_id' => $this->branch->id,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->admin)->post(route('telecom.phone-numbers.transfer', $phone->id), [
            'assigned_type' => 'Employee',
            'user_id' => $this->admin->id,
            'transfer_reason' => 'Assigned to IT Admin for on-call duty',
        ]);

        $response->assertRedirect(route('telecom.phone-numbers.index'));

        $phone->refresh();
        $this->assertEquals('Employee', $phone->assigned_type);
        $this->assertEquals($this->admin->id, $phone->user_id);
    }

    public function test_phone_numbers_csv_export()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.phone-numbers.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_broadbands_index_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.broadbands.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_broadband()
    {
        $acc = 'BB-TEST-' . rand(10000, 99999);
        $payload = [
            'account_number' => $acc,
            'service_number' => '011555' . rand(1000, 9999),
            'connection_name' => 'HQ Dedicated Fiber',
            'connection_type' => 'Fiber',
            'telecom_provider_id' => $this->provider->id,
            'package_type' => '50 Mbps Enterprise Dedicated',
            'bandwidth_speed' => '50 Mbps',
            'monthly_cost' => 15000.00,
            'billing_type' => 'Postpaid',
            'branch_id' => $this->branch->id,
            'installation_address' => 'HQ 4th Floor Server Room',
            'ip_address' => '196.188.12.34',
            'status' => 'Active',
        ];

        $response = $this->actingAs($this->admin)->post(route('telecom.broadbands.store'), $payload);
        $response->assertRedirect(route('telecom.broadbands.index'));

        $this->assertDatabaseHas('telecom_broadbands', [
            'account_number' => $acc,
            'connection_name' => 'HQ Dedicated Fiber',
        ]);
    }

    public function test_broadbands_csv_export()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.broadbands.export'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_providers_index_accessible()
    {
        $response = $this->actingAs($this->admin)->get(route('telecom.providers.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_provider()
    {
        $code = 'REGIONAL_ISP_' . rand(100, 999);
        $payload = [
            'name' => 'New Regional ISP',
            'code' => $code,
            'support_contact' => '+251118999888',
            'is_active' => true,
            'notes' => 'Backup wireless internet link',
        ];

        $response = $this->actingAs($this->admin)->post(route('telecom.providers.store'), $payload);
        $response->assertRedirect(route('telecom.providers.index'));

        $this->assertDatabaseHas('telecom_providers', [
            'code' => $code,
        ]);
    }
}
