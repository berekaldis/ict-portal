<?php

namespace Database\Seeders;

use App\Models\TelecomBroadband;
use App\Models\TelecomPhoneNumber;
use App\Models\TelecomProvider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TelecomModuleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Each Granular Permission for Telecom Management
        $permissions = [
            // Standard dot-notation
            'telecom.view',
            'telecom.phone.view',
            'telecom.phone.create',
            'telecom.phone.edit',
            'telecom.phone.delete',
            'telecom.phone.transfer',
            'telecom.broadband.view',
            'telecom.broadband.create',
            'telecom.broadband.edit',
            'telecom.broadband.delete',
            'telecom.broadband.transfer',
            'telecom.providers.manage',
            'telecom.export',
            // Legacy aliases from company system
            'view telecom management',
            'manage telecom connections',
            'export telecom data',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Grant to Super Admin and IT Admin roles
        $roles = Role::whereIn('name', ['Super Admin', 'IT Admin', 'Admin'])->get();
        foreach ($roles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Grant to all Super Admin & IT Admin users
        $adminUsers = User::all();
        foreach ($adminUsers as $user) {
            if ($user->hasRole('Super Admin') || $user->hasRole('IT Admin') || $user->username === 'superadmin') {
                $user->givePermissionTo($permissions);
            }
        }

        // 2. Seed Default Telecom Providers (Ethio Telecom & Safaricom Ethiopia)
        $ethio = TelecomProvider::firstOrCreate(
            ['name' => 'Ethio Telecom'],
            [
                'code' => 'ETHIO_TELECOM',
                'support_contact' => '994 / support@ethiotelecom.et',
                'is_active' => true,
                'notes' => 'Primary National Telecom Operator in Ethiopia (Voice, WTTx, Enterprise Fiber)',
            ]
        );

        $safari = TelecomProvider::firstOrCreate(
            ['name' => 'Safaricom Ethiopia'],
            [
                'code' => 'SAFARICOM',
                'support_contact' => '700 / support@safaricom.et',
                'is_active' => true,
                'notes' => 'Secondary Telecom Operator in Ethiopia (4G/5G SIMs, Enterprise Fixed Data)',
            ]
        );

        // 3. Seed Sample Phone Numbers if empty
        if (TelecomPhoneNumber::count() === 0) {
            $firstBranch = DB::table('branches')->first();
            $firstDept = DB::table('departments')->first();
            $adminUser = User::first();

            TelecomPhoneNumber::create([
                'phone_number' => '+251 91 123 4567',
                'account_number' => 'ACC-ETH-89102',
                'sim_card_number' => '89251012345678901',
                'telecom_provider_id' => $ethio->id,
                'service_type' => 'Mobile Voice',
                'package_type' => 'Enterprise CUG Unlimited',
                'package_start_date' => now()->startOfMonth(),
                'package_expiry_date' => now()->addMonths(6),
                'monthly_cost' => 1250.00,
                'billing_type' => 'Postpaid',
                'assigned_type' => 'Branch',
                'branch_id' => $firstBranch?->id,
                'department_id' => $firstDept?->id,
                'user_id' => $adminUser?->id,
                'employee_id' => $adminUser?->id,
                'status' => 'Active',
                'issue_date' => now()->subMonths(3),
                'renewal_date' => now()->addMonths(9),
                'notes' => 'Official cashier mobile hotline for branch operations',
            ]);

            TelecomPhoneNumber::create([
                'phone_number' => '+251 97 765 4321',
                'account_number' => 'ACC-SAF-11234',
                'sim_card_number' => '89251098765432100',
                'telecom_provider_id' => $safari->id,
                'service_type' => 'Mobile Data',
                'package_type' => '4G Unlimited Monthly Data',
                'package_start_date' => now()->startOfMonth(),
                'package_expiry_date' => now()->addMonth(),
                'monthly_cost' => 1800.00,
                'billing_type' => 'Postpaid',
                'assigned_type' => 'Department',
                'department_id' => $firstDept?->id,
                'status' => 'Active',
                'issue_date' => now()->subMonths(1),
                'renewal_date' => now()->addMonths(5),
                'notes' => 'Backup SIM router data line',
            ]);
        }

        // 4. Seed Sample Broadband Connections if empty
        if (TelecomBroadband::count() === 0) {
            $firstBranch = DB::table('branches')->first();
            $firstDept = DB::table('departments')->first();

            TelecomBroadband::create([
                'account_number' => 'WTTX-BOLE-001',
                'service_number' => 'SRV-011-66789',
                'connection_name' => 'Bole Medhanialem WTTx Primary',
                'connection_type' => 'WTTx (Fixed Wireless)',
                'telecom_provider_id' => $ethio->id,
                'package_type' => 'WTTx Business Premium 20Mbps',
                'package_start_date' => now()->subMonths(2),
                'package_expiry_date' => now()->addMonths(10),
                'bandwidth_speed' => '20 Mbps',
                'monthly_cost' => 3850.00,
                'billing_type' => 'Postpaid',
                'branch_id' => $firstBranch?->id,
                'department_id' => $firstDept?->id,
                'installation_address' => 'Bole Medhanialem Mall, Ground Floor, Addis Ababa',
                'ip_address' => '196.188.120.45',
                'equipment_details' => 'Huawei 5G CPE Pro Router (SN: HW-CPE-987123)',
                'contract_start_date' => now()->subMonths(2),
                'contract_expiry_date' => now()->addMonths(10),
                'status' => 'Active',
                'notes' => 'Primary internet connection for POS terminals and ERP sync',
            ]);

            TelecomBroadband::create([
                'account_number' => 'FIBER-HQ-001',
                'service_number' => 'FBR-011-55123',
                'connection_name' => 'Head Office Dedicated Fiber',
                'connection_type' => 'Fiber',
                'telecom_provider_id' => $ethio->id,
                'package_type' => 'Dedicated DIA 50Mbps 1:1',
                'package_start_date' => now()->subMonths(6),
                'package_expiry_date' => now()->addMonths(6),
                'bandwidth_speed' => '50 Mbps',
                'monthly_cost' => 14500.00,
                'billing_type' => 'Postpaid',
                'branch_id' => null,
                'department_id' => $firstDept?->id,
                'installation_address' => 'Head Office, 4th Floor Server Room',
                'ip_address' => '197.156.77.10',
                'equipment_details' => 'Cisco Fiber Media Converter & Gigabit Gateway',
                'contract_start_date' => now()->subMonths(6),
                'contract_expiry_date' => now()->addMonths(6),
                'status' => 'Active',
                'notes' => 'Central data center & VPN backbone link',
            ]);
        }
    }
}
