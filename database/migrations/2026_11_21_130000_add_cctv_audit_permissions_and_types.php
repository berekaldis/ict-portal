<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create CCTV permissions
        $permissions = [
            'cctv_audit.view' => 'View CCTV Audit & Checklists',
            'cctv_audit.manage' => 'Manage CCTV Registers & Incidents',
        ];

        $now = now();
        foreach ($permissions as $name => $desc) {
            $exists = DB::table('permissions')->where('name', $name)->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Grant to roles that have it_survey.view or admin/super-admin
        try {
            $adminRoles = DB::table('roles')->whereIn('name', ['super-admin', 'admin', 'IT Admin', 'IT Manager', 'IT Support'])->get();
            $permIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id');

            foreach ($adminRoles as $role) {
                foreach ($permIds as $permId) {
                    $has = DB::table('role_has_permissions')
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permId)
                        ->exists();
                    if (!$has) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $role->id,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {}

        // 2. Change 'type' column in it_survey_categories to string so 'cctv' is fully supported
        try {
            if (Schema::hasTable('it_survey_categories')) {
                DB::statement("ALTER TABLE `it_survey_categories` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'hardware'");
            }
        } catch (\Throwable $e) {}

        // 3. Change 'type' column in it_survey_reports to string so 'cctv' is fully supported
        try {
            if (Schema::hasTable('it_survey_reports')) {
                DB::statement("ALTER TABLE `it_survey_reports` MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'hardware'");
            }
        } catch (\Throwable $e) {}

        // 4. Ensure only One-Time and Daily categories exist for CCTV
        if (Schema::hasTable('it_survey_categories') && Schema::hasTable('it_survey_checklist_items')) {
            // Remove any Weekly, Monthly, Quarterly CCTV categories
            $toRemove = DB::table('it_survey_categories')
                ->where('name', 'LIKE', 'CCTV – 3.%')
                ->orWhere('name', 'LIKE', 'CCTV – 4.%')
                ->orWhere('name', 'LIKE', 'CCTV – 5.%')
                ->pluck('id');

            if ($toRemove->isNotEmpty()) {
                DB::table('it_survey_checklist_items')->whereIn('category_id', $toRemove)->delete();
                DB::table('it_survey_categories')->whereIn('id', $toRemove)->delete();
            }

            // Seed/Update 1. One-time Setup
            $cat1 = DB::table('it_survey_categories')->where('name', 'LIKE', 'CCTV – 1.%')->first();
            if (!$cat1) {
                $cat1Id = DB::table('it_survey_categories')->insertGetId([
                    'name' => 'CCTV – 1. One-time Setup (verify per branch)',
                    'icon' => 'fas fa-shield-alt',
                    'type' => 'cctv',
                    'has_photo' => true,
                    'description' => 'Verify one-time security hardening, accounts, VLAN, NTP, and HDD setup per Dahua standards.',
                    'display_order' => 10,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $cat1Id = $cat1->id;
                DB::table('it_survey_categories')->where('id', $cat1Id)->update([
                    'name' => 'CCTV – 1. One-time Setup (verify per branch)',
                    'type' => 'cctv',
                    'display_order' => 10,
                ]);
            }

            $cat1Items = [
                'Default passwords changed on NVR and all cameras (unique strong password per branch)',
                'Admin accounts limited; one account per person; ex-staff removed',
                'P2P/Cloud and UPnP off unless needed; remote access via VPN only (no port-forwarding)',
                'CCTV on separate VLAN/network from office PCs and guest Wi-Fi',
                'NTP set, time zone GMT+3 (Addis Ababa), correct date/time',
                'Surveillance-grade HDDs installed (WD Purple / Seagate SkyHawk)',
                'Recording mode set (continuous or motion) and retention target set (e.g. 30 days)',
                'Auto-maintenance/reboot time set (off-hours)',
                'UPS connected to NVR, PoE switch and router',
                'Branch register filled: NVR model/IP, camera count, HDD size, firmware, serial numbers',
                'NVR config backup exported and stored',
            ];

            // Re-sync items for Cat 1
            DB::table('it_survey_checklist_items')->where('category_id', $cat1Id)->delete();
            foreach ($cat1Items as $idx => $q) {
                DB::table('it_survey_checklist_items')->insert([
                    'category_id' => $cat1Id,
                    'question' => $q,
                    'help_text' => null,
                    'display_order' => $idx + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Seed/Update 2. Daily Check
            $cat2 = DB::table('it_survey_categories')->where('name', 'LIKE', 'CCTV – 2.%')->first();
            if (!$cat2) {
                $cat2Id = DB::table('it_survey_categories')->insertGetId([
                    'name' => 'CCTV – 2. Daily (5 min – Helpdesk, remote)',
                    'icon' => 'fas fa-desktop',
                    'type' => 'cctv',
                    'has_photo' => false,
                    'description' => 'Daily remote health audit via SmartPSS / DMSS / NVR web portal.',
                    'display_order' => 20,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $cat2Id = $cat2->id;
                DB::table('it_survey_categories')->where('id', $cat2Id)->update([
                    'name' => 'CCTV – 2. Daily (5 min – Helpdesk, remote)',
                    'type' => 'cctv',
                    'display_order' => 20,
                ]);
            }

            $cat2Items = [
                'All cameras online (SmartPSS / DMSS / NVR web)',
                'NVR recording on all channels',
                'NVR date/time correct',
                'No HDD error or disk-full alert',
            ];

            DB::table('it_survey_checklist_items')->where('category_id', $cat2Id)->delete();
            foreach ($cat2Items as $idx => $q) {
                DB::table('it_survey_checklist_items')->insert([
                    'category_id' => $cat2Id,
                    'question' => $q,
                    'help_text' => null,
                    'display_order' => $idx + 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', ['cctv_audit.view', 'cctv_audit.manage'])->delete();
    }
};
