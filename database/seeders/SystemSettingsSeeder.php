<?php

namespace Database\Seeders;

use App\Models\CaseAssignee;
use App\Models\CaseSetting;
use App\Models\CaseType;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Seed distinct Telegram configurations for Edit Request and Case Management from legacy systems.
     */
    public function run(): void
    {
        $settings = [
            // ============================================================
            // 1. EDIT REQUEST TELEGRAM CONFIGURATION (edit_request/config.php)
            // ============================================================
            'edit_request_telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
            'edit_request_telegram_chat_id_admin' => '-1001502645326',          // IT Admin Group (Execution)
            'edit_request_telegram_chat_id_finance' => '-1003609053132',        // Finance Manager Group
            'edit_request_telegram_chat_id_fb' => '-1003821366530',             // F&B Manager Group
            'edit_request_telegram_chat_id_onhand_delete' => '-1002426639974', // On Hand Delete Done/Reject Group
            'edit_request_telegram_coordinator_chat_id' => '814523272',         // Training & Coordinator Chat
            'edit_request_telegram_branch_managers_group_chat_id' => '1626288394', // Branch Managers Broadcast
            'edit_request_telegram_departments_group_chat_id' => '6492922880',     // All Departments Broadcast

            // ============================================================
            // 2. CASE MODULE TELEGRAM CONFIGURATION (case/case_config.php)
            // ============================================================
            'case_telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
            'case_telegram_group_chat_id' => '-1001502645326', // IT Case Alerts Group
            'case_notify_assignee_direct' => '1',              // Direct message to assignee Telegram ID

            // Backward compatibility aliases
            'telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
            'telegram_chat_id_admin' => '-1001502645326',
            'telegram_chat_id_finance' => '-1003609053132',
            'telegram_chat_id_fb' => '-1003821366530',
            'telegram_chat_id_onhand_delete' => '-1002426639974',
            'telegram_coordinator_chat_id' => '814523272',
            'telegram_branch_managers_group_chat_id' => '1626288394',
            'telegram_departments_group_chat_id' => '6492922880',

            // Company metadata
            'company_name' => "Kaldi's Coffee ICT Portal",
            'company_phone' => '+251 11 661 6161',
            'support_email' => 'support.ict@kaldisbunna.et',
        ];

        foreach ($settings as $key => $val) {
            CaseSetting::set($key, $val);
        }

        // 2. Default Case Assignees with Telegram IDs
        $assignees = [
            ['name' => 'IT Support Team', 'telegram_id' => '-1001502645326', 'status' => 'active'],
            ['name' => 'Network Admin', 'telegram_id' => '814523272', 'status' => 'active'],
            ['name' => 'Hardware Specialist', 'telegram_id' => '814523272', 'status' => 'active'],
            ['name' => 'POS & CNET Specialist', 'telegram_id' => '-1001502645326', 'status' => 'active'],
        ];

        foreach ($assignees as $a) {
            CaseAssignee::firstOrCreate(
                ['name' => $a['name']],
                ['telegram_id' => $a['telegram_id'], 'status' => $a['status']]
            );
        }

        // 3. Default Case Categories
        $types = [
            'CNET POS & Billing Error',
            'Network & Internet Connectivity',
            'Printer / Hardware Malfunction',
            'User Account & Password Reset',
        ];

        foreach ($types as $name) {
            CaseType::firstOrCreate(['name' => $name]);
        }
    }
}
