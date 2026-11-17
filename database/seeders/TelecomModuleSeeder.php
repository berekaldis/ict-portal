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
    }
}
