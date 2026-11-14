<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class KaldisCommunicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'communication.view' => 'View Kaldis Branch Communication Hub',
            'communication.manage' => 'Manage Communication Bot Config & Topics',
            'communication.moderation' => 'Manage Group Moderation & Member Restrictions',
            'communication.broadcast' => 'Send Broadcast Announcements to Regions',
            'communication.sync' => 'Force Sync Members & Standard Topics',
            // Legacy aliases
            'view kaldis communication' => 'View Kaldis Branch Communication',
            'manage kaldis communication' => 'Manage Kaldis Branch Communication',
            'manage communication moderation' => 'Manage Communication Moderation',
            'send communication broadcast' => 'Send Communication Broadcast',
        ];

        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Assign to Super Admin and IT Admin
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $itAdmin = Role::firstOrCreate(['name' => 'IT Admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $allPerms = array_keys($permissions);
        $superAdmin->givePermissionTo($allPerms);
        $itAdmin->givePermissionTo($allPerms);
        $admin->givePermissionTo($allPerms);

        // Assign to active admin users
        $adminUsers = User::whereIn('role', ['admin', 'superadmin'])->get();
        foreach ($adminUsers as $user) {
            $user->givePermissionTo($allPerms);
        }
    }
}
