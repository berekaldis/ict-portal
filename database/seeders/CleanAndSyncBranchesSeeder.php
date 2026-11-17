<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanAndSyncBranchesSeeder extends Seeder
{
    /**
     * Run the database seeds:
     * 1. Truncate branches table and re-sync active branches from company_system.
     * 2. Remove sample/migrated data from HR, Sales Reconciliation, IT Branch Survey, Telecom Management, and Kaldis Communication.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // ==========================================
        // 1. SYNC ACTIVE BRANCHES FROM COMPANY SYSTEM
        // ==========================================
        DB::table('branches')->truncate();

        try {
            $csBranches = DB::connection('company_system')
                ->table('branches')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            $activeNames = [];
            foreach ($csBranches as $b) {
                $name = trim($b->name);
                if (empty($name)) continue;
                $activeNames[] = $name;

                DB::table('branches')->insert([
                    'id' => $b->id,
                    'branch_name' => $name,
                    'is_active' => 1,
                    'telegram_chat_id' => $b->telegram_chat_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Sync hr_settings branches
            if (DB::getSchemaBuilder()->hasTable('hr_settings')) {
                DB::table('hr_settings')->where('type', 'branch')->delete();
                foreach ($csBranches as $b) {
                    $name = trim($b->name);
                    if (empty($name)) continue;

                    DB::table('hr_settings')->insert([
                        'type' => 'branch',
                        'value' => $name,
                        'chat_id' => $b->telegram_chat_id ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Company System connection unavailable during seeding: " . $e->getMessage());
        }

        // ==========================================
        // 2. REMOVE MIGRATED / SAMPLE DATA
        // ==========================================
        // HR
        if (DB::getSchemaBuilder()->hasTable('hr_internship_requests')) DB::table('hr_internship_requests')->truncate();
        if (DB::getSchemaBuilder()->hasTable('hr_qualifications')) DB::table('hr_qualifications')->truncate();
        if (DB::getSchemaBuilder()->hasTable('hr_recruitments')) DB::table('hr_recruitments')->truncate();

        // Sales Reconciliation
        if (DB::getSchemaBuilder()->hasTable('sales_cases')) DB::table('sales_cases')->truncate();
        if (DB::getSchemaBuilder()->hasTable('sales_case_attachments')) DB::table('sales_case_attachments')->truncate();
        if (DB::getSchemaBuilder()->hasTable('sales_case_comments')) DB::table('sales_case_comments')->truncate();
        if (DB::getSchemaBuilder()->hasTable('sales_case_history')) DB::table('sales_case_history')->truncate();
        if (DB::getSchemaBuilder()->hasTable('sales_case_sequence')) DB::table('sales_case_sequence')->truncate();
        if (DB::getSchemaBuilder()->hasTable('sales_settings')) DB::table('sales_settings')->truncate();

        // IT Branch Survey
        if (DB::getSchemaBuilder()->hasTable('surveys')) DB::table('surveys')->truncate();
        if (DB::getSchemaBuilder()->hasTable('question_surveys')) DB::table('question_surveys')->truncate();
        if (DB::getSchemaBuilder()->hasTable('it_survey_reports')) DB::table('it_survey_reports')->truncate();
        if (DB::getSchemaBuilder()->hasTable('it_hardware_assets')) DB::table('it_hardware_assets')->truncate();

        // Telecom Management
        if (DB::getSchemaBuilder()->hasTable('telecom_phone_numbers')) DB::table('telecom_phone_numbers')->truncate();
        if (DB::getSchemaBuilder()->hasTable('telecom_broadbands')) DB::table('telecom_broadbands')->truncate();
        if (DB::getSchemaBuilder()->hasTable('telecom_transfers')) DB::table('telecom_transfers')->truncate();
        if (DB::getSchemaBuilder()->hasTable('telecom_providers')) {
            DB::table('telecom_providers')->whereNotIn('code', ['ETHIO_TELECOM', 'SAFARICOM'])->delete();
        }

        // Kaldis Communication
        if (DB::getSchemaBuilder()->hasTable('tg_members')) DB::table('tg_members')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_telegram_groups')) DB::table('tg_telegram_groups')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_group_members')) DB::table('tg_group_members')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_group_authorized_senders')) DB::table('tg_group_authorized_senders')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_message_responses')) DB::table('tg_message_responses')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_tracked_messages')) DB::table('tg_tracked_messages')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_telegram_updates')) DB::table('tg_telegram_updates')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_audit_logs')) DB::table('tg_audit_logs')->truncate();
        if (DB::getSchemaBuilder()->hasTable('tg_login_attempts')) DB::table('tg_login_attempts')->truncate();

        $legacyTables = ['members', 'telegram_groups', 'group_members', 'group_authorized_senders', 'message_responses', 'tracked_messages', 'telegram_updates'];
        foreach ($legacyTables as $lt) {
            if (DB::getSchemaBuilder()->hasTable($lt)) {
                DB::table($lt)->truncate();
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
