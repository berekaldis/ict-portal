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

        // Exactly the 28 active operational branches
        $canonicalBranches = [
            ['id' => 130068, 'branch_name' => '4 kilo', 'telegram_chat_id' => null],
            ['id' => 33, 'branch_name' => 'Adama German', 'telegram_chat_id' => null],
            ['id' => 13, 'branch_name' => 'Addisu Gebya', 'telegram_chat_id' => null],
            ['id' => 46, 'branch_name' => 'Airport', 'telegram_chat_id' => null],
            ['id' => 130469, 'branch_name' => 'Airport 2', 'telegram_chat_id' => null],
            ['id' => 49, 'branch_name' => 'Betel Adebabay', 'telegram_chat_id' => null],
            ['id' => 12, 'branch_name' => 'Betel Bicha Foq', 'telegram_chat_id' => null],
            ['id' => 130069, 'branch_name' => 'Betel Total', 'telegram_chat_id' => null],
            ['id' => 39, 'branch_name' => 'Bishoftu', 'telegram_chat_id' => null],
            ['id' => 35, 'branch_name' => 'Bole Medhanialem', 'telegram_chat_id' => null],
            ['id' => 40, 'branch_name' => 'Bulbula 93', 'telegram_chat_id' => null],
            ['id' => 56, 'branch_name' => 'Bulbula Medhanialm', 'telegram_chat_id' => null],
            ['id' => 14, 'branch_name' => 'ECA', 'telegram_chat_id' => null],
            ['id' => 29, 'branch_name' => 'Entoto Park', 'telegram_chat_id' => null],
            ['id' => 17, 'branch_name' => 'Grand Palace', 'telegram_chat_id' => null],
            ['id' => 9, 'branch_name' => 'Gulele Medhanialm', 'telegram_chat_id' => null],
            ['id' => 43, 'branch_name' => 'Gurdshola', 'telegram_chat_id' => null],
            ['id' => 20, 'branch_name' => 'Imperial', 'telegram_chat_id' => null],
            ['id' => 44, 'branch_name' => 'Jemo', 'telegram_chat_id' => null],
            ['id' => 8, 'branch_name' => 'Kality Sheger', 'telegram_chat_id' => null],
            ['id' => 4, 'branch_name' => 'Kera', 'telegram_chat_id' => null],
            ['id' => 11, 'branch_name' => 'Lancha', 'telegram_chat_id' => '6492922880'],
            ['id' => 10, 'branch_name' => 'Megenaga', 'telegram_chat_id' => null],
            ['id' => 11606, 'branch_name' => 'Mexico', 'telegram_chat_id' => null],
            ['id' => 58, 'branch_name' => 'Old Airport', 'telegram_chat_id' => null],
            ['id' => 7, 'branch_name' => 'Sarbet', 'telegram_chat_id' => null],
            ['id' => 6, 'branch_name' => 'Semen Hotel', 'telegram_chat_id' => null],
            ['id' => 32, 'branch_name' => 'Torhailoch', 'telegram_chat_id' => null],
        ];

        $branchesToInsert = $canonicalBranches;

        try {
            $csBranches = DB::connection('company_system')
                ->table('branches')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            if ($csBranches->isNotEmpty()) {
                $branchesToInsert = [];
                foreach ($csBranches as $b) {
                    $name = trim($b->name);
                    if (empty($name)) continue;
                    $branchesToInsert[] = [
                        'id' => $b->id,
                        'branch_name' => $name,
                        'telegram_chat_id' => $b->telegram_chat_id ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Company System connection unavailable during seeding: " . $e->getMessage());
        }

        foreach ($branchesToInsert as $b) {
            DB::table('branches')->updateOrInsert(
                ['branch_name' => $b['branch_name']],
                [
                    'id' => $b['id'],
                    'is_active' => 1,
                    'telegram_chat_id' => $b['telegram_chat_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Sync hr_settings branches
        if (DB::getSchemaBuilder()->hasTable('hr_settings')) {
            DB::table('hr_settings')->where('type', 'branch')->delete();
            foreach ($branchesToInsert as $b) {
                DB::table('hr_settings')->insert([
                    'type' => 'branch',
                    'value' => $b['branch_name'],
                    'chat_id' => $b['telegram_chat_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Ensure standard jobs exist
            $standardJobs = [
                'Barista', 'Cashier', 'Waiter', 'Cook', 'Cleaner',
                'Supervisor', 'Manager', 'Driver', 'Guard', 'Accountant',
                'IT Support', 'Store Keeper'
            ];
            foreach ($standardJobs as $job) {
                DB::table('hr_settings')->updateOrInsert(
                    ['type' => 'job', 'value' => $job],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            // Ensure standard ranks exist
            $standardRanks = [
                'Branch Manager', 'Supervisor', 'Team Leader', 'Senior Staff', 'Junior Staff'
            ];
            foreach ($standardRanks as $rank) {
                DB::table('hr_settings')->updateOrInsert(
                    ['type' => 'rank', 'value' => $rank],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
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
