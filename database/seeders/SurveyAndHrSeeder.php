<?php

namespace Database\Seeders;

use App\Models\HrSetting;
use App\Models\HrSystemConfig;
use App\Models\Survey;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SurveyAndHrSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions for Survey & HR
        $permissions = [
            'survey.view',
            'survey.manage',
            'hr.view',
            'hr.create',
            'hr.manage',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // Assign to Super Admin & IT Admin
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        $itAdmin = Role::where('name', 'IT Admin')->first();
        if ($itAdmin) {
            $itAdmin->givePermissionTo($permissions);
        }

        // 2. Default HR Settings
        $branches = [
            'Bole', 'Piassa', 'Merkato', 'CMC', 'Kazanchis',
            'Saris', 'Gulen', 'Megenagna', '4 Kilo', 'Goro',
            'Sarbet', 'Mexico', 'Gerji', 'Jemo', 'Ayat'
        ];
        foreach ($branches as $b) {
            HrSetting::firstOrCreate(['type' => 'branch', 'value' => $b]);
        }

        $jobs = [
            'Barista', 'Cashier', 'Waiter', 'Cook', 'Cleaner',
            'Supervisor', 'Manager', 'Driver', 'Guard', 'Accountant',
            'IT Support', 'Store Keeper'
        ];
        foreach ($jobs as $j) {
            HrSetting::firstOrCreate(['type' => 'job', 'value' => $j]);
        }

        $ranks = [
            'Branch Manager', 'Supervisor', 'Team Leader', 'Senior Staff', 'Junior Staff'
        ];
        foreach ($ranks as $r) {
            HrSetting::firstOrCreate(['type' => 'rank', 'value' => $r]);
        }

        HrSetting::firstOrCreate(['type' => 'logo', 'value' => '/images/kaldis-logo.png']);

        // Default HR bot token
        if (!HrSystemConfig::get('bot_token')) {
            HrSystemConfig::set('bot_token', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8');
        }

        // 3. Import existing surveys from Servey/data/submissions.jsonl if empty
        if (Survey::count() === 0) {
            $jsonlPath = base_path('Servey/data/submissions.jsonl');
            if (file_exists($jsonlPath)) {
                $lines = file($jsonlPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $data = json_decode($line, true);
                    if ($data && is_array($data)) {
                        $createdAt = isset($data['logged_at']) ? $data['logged_at'] : now();
                        unset($data['logged_at']);
                        Survey::create(array_merge($data, [
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]));
                    }
                }
            }
        }
    }
}
