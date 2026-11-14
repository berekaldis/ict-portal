<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'username' => 'superadmin',
                'name' => "Kaldi's Super Admin",
                'full_name' => "Kaldi's ICT Super Administrator",
                'email' => 'superadmin@kaldiscoffee.com',
                'role' => 'Super Admin',
                'department' => 'IT',
                'password' => 'admin123',
                'chat_id' => '814523272',
                'roles' => ['Super Admin', 'superadmin'],
            ],
            [
                'username' => 'admin',
                'name' => "Kaldi's Department Admin",
                'full_name' => "Finance Department Administrator",
                'email' => 'admin@kaldiscoffee.com',
                'role' => 'Admin',
                'department' => 'Finance',
                'password' => 'admin123',
                'chat_id' => '-1003609053132',
                'roles' => ['Admin', 'Finance Manager'],
            ],
            [
                'username' => 'finance_admin',
                'name' => 'Finance Department Admin',
                'full_name' => 'Finance Department Administrator',
                'email' => 'finance.admin@kaldiscoffee.com',
                'role' => 'Admin',
                'department' => 'Finance',
                'password' => 'admin123',
                'chat_id' => '-1003609053132',
                'roles' => ['Admin', 'Finance Manager'],
            ],
            [
                'username' => 'fb_admin',
                'name' => 'F&B Department Admin',
                'full_name' => 'Food & Beverage Administrator',
                'email' => 'fb.admin@kaldiscoffee.com',
                'role' => 'Admin',
                'department' => 'F&B',
                'password' => 'admin123',
                'chat_id' => '-1003821366530',
                'roles' => ['Admin', 'F&B Manager'],
            ],
            [
                'username' => 'it_admin',
                'name' => 'IT Department Admin',
                'full_name' => 'IT Support Administrator',
                'email' => 'it.admin@kaldiscoffee.com',
                'role' => 'IT Admin',
                'department' => 'IT',
                'password' => 'admin123',
                'chat_id' => '814523272',
                'roles' => ['Admin', 'IT Admin', 'IT Staff'],
            ],
            [
                'username' => 'it_trainer',
                'name' => 'IT Support Team',
                'full_name' => 'Kaldi IT Support Officer',
                'email' => 'it@kaldiscoffee.com',
                'role' => 'IT Staff',
                'department' => 'IT',
                'password' => 'admin123',
                'chat_id' => '814523272',
                'roles' => ['IT Staff'],
            ],
            [
                'username' => 'finance_trainer',
                'name' => 'Finance Manager',
                'full_name' => 'Finance Department Approver',
                'email' => 'finance@kaldiscoffee.com',
                'role' => 'Admin',
                'department' => 'Finance',
                'password' => 'admin123',
                'chat_id' => '-1003609053132',
                'roles' => ['Admin', 'Finance Manager'],
            ],
            [
                'username' => 'fb_mgr',
                'name' => 'F&B Manager',
                'full_name' => 'Food & Beverage Manager',
                'email' => 'fb@kaldiscoffee.com',
                'role' => 'Admin',
                'department' => 'F&B',
                'password' => 'admin123',
                'chat_id' => '-1003821366530',
                'roles' => ['Admin', 'F&B Manager'],
            ],
            [
                'username' => 'coordinator',
                'name' => 'Training Coordinator',
                'full_name' => 'Training & Operations Coordinator',
                'email' => 'coordinator@kaldiscoffee.com',
                'role' => 'Coordinator',
                'department' => 'HR / Training',
                'password' => 'admin123',
                'chat_id' => '',
                'roles' => ['Coordinator'],
            ],
            [
                'username' => 'branch_user',
                'name' => 'Bole Branch User',
                'full_name' => 'Bole Branch Requester',
                'email' => 'branch.user@kaldiscoffee.com',
                'role' => 'Branch User',
                'department' => 'Operations',
                'branch' => 'Bole',
                'password' => 'admin123',
                'chat_id' => '',
                'roles' => ['Branch User'],
            ],
            [
                'username' => 'bole_branch',
                'name' => 'Bole Branch Manager',
                'full_name' => 'Bole Branch Store',
                'email' => 'bole@kaldiscoffee.com',
                'role' => 'Branch User',
                'department' => 'Operations',
                'branch' => 'Bole',
                'password' => 'admin123',
                'chat_id' => '',
                'roles' => ['Branch User'],
            ],
        ];

        foreach ($users as $u) {
            $user = User::updateOrCreate(
                ['username' => $u['username']],
                [
                    'name' => $u['name'],
                    'full_name' => $u['full_name'],
                    'email' => $u['email'],
                    'role' => $u['role'],
                    'department' => $u['department'],
                    'branch' => $u['branch'] ?? null,
                    'password' => Hash::make($u['password']),
                    'chat_id' => $u['chat_id'],
                    'is_active' => true,
                    'must_change_password' => false,
                ]
            );

            $user->syncRoles($u['roles']);
        }
    }
}
