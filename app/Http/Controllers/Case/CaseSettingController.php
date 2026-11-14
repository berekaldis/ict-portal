<?php

namespace App\Http\Controllers\Case;

use App\Http\Controllers\Controller;
use App\Models\CaseAssignee;
use App\Models\CaseSetting;
use App\Models\CaseType;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseSettingController extends Controller
{
    /**
     * Default settings originating from legacy edit_request and case modules.
     */
    protected array $defaultSettings = [
        // 1. Edit & Void Requests Telegram Configuration
        'edit_request_telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
        'edit_request_telegram_chat_id_admin' => '-1001502645326',
        'edit_request_telegram_chat_id_finance' => '-1003609053132',
        'edit_request_telegram_chat_id_fb' => '-1003821366530',
        'edit_request_telegram_chat_id_onhand_delete' => '-1002426639974',
        'edit_request_telegram_coordinator_chat_id' => '814523272',
        'edit_request_telegram_branch_managers_group_chat_id' => '1626288394',
        'edit_request_telegram_departments_group_chat_id' => '6492922880',

        // 2. Case Management Telegram Configuration
        'case_telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
        'case_telegram_group_chat_id' => '-1001502645326',
        'case_notify_assignee_direct' => '1',

        // Legacy compatibility fallbacks
        'telegram_bot_token' => '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8',
        'telegram_chat_id_admin' => '-1001502645326',
        'telegram_chat_id_finance' => '-1003609053132',
        'telegram_chat_id_fb' => '-1003821366530',
        'telegram_chat_id_onhand_delete' => '-1002426639974',

        // 3. General & Branding
        'company_name' => "Kaldi's Coffee ICT Portal",
        'company_phone' => '+251 11 661 6161',
        'support_email' => 'support.ict@kaldiscoffee.com',
    ];

    public function index(): Response
    {
        $types = CaseType::orderBy('name')->get();
        $assignees = CaseAssignee::orderBy('name')->get();
        $savedSettings = CaseSetting::pluck('setting_value', 'setting_key')->toArray();

        // Merge defaults with saved settings
        $settings = array_merge($this->defaultSettings, $savedSettings);

        // Ensure legacy values are mirrored if not explicitly set in new separated keys
        if (empty($settings['edit_request_telegram_bot_token']) && !empty($settings['telegram_bot_token'])) {
            $settings['edit_request_telegram_bot_token'] = $settings['telegram_bot_token'];
        }
        if (empty($settings['case_telegram_bot_token']) && !empty($settings['telegram_bot_token'])) {
            $settings['case_telegram_bot_token'] = $settings['telegram_bot_token'];
        }
        if (empty($settings['case_telegram_group_chat_id']) && !empty($settings['telegram_chat_id_admin'])) {
            $settings['case_telegram_group_chat_id'] = $settings['telegram_chat_id_admin'];
        }

        return Inertia::render('cases/settings', [
            'types' => $types,
            'assignees' => $assignees,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Edit Request settings
            'edit_request_telegram_bot_token' => 'nullable|string|max:255',
            'edit_request_telegram_chat_id_admin' => 'nullable|string|max:100',
            'edit_request_telegram_chat_id_finance' => 'nullable|string|max:100',
            'edit_request_telegram_chat_id_fb' => 'nullable|string|max:100',
            'edit_request_telegram_chat_id_onhand_delete' => 'nullable|string|max:100',
            'edit_request_telegram_coordinator_chat_id' => 'nullable|string|max:100',
            'edit_request_telegram_branch_managers_group_chat_id' => 'nullable|string|max:100',
            'edit_request_telegram_departments_group_chat_id' => 'nullable|string|max:100',

            // Case Management settings
            'case_telegram_bot_token' => 'nullable|string|max:255',
            'case_telegram_group_chat_id' => 'nullable|string|max:100',
            'case_notify_assignee_direct' => 'nullable|string|in:0,1',

            // General & Branding
            'company_name' => 'nullable|string|max:200',
            'company_phone' => 'nullable|string|max:100',
            'support_email' => 'nullable|email|max:150',
        ]);

        foreach ($validated as $key => $val) {
            CaseSetting::set($key, (string) ($val ?? ''));
        }

        // Also keep legacy keys in sync for backward compatibility
        if (isset($validated['edit_request_telegram_bot_token'])) {
            CaseSetting::set('telegram_bot_token', $validated['edit_request_telegram_bot_token']);
        }
        if (isset($validated['edit_request_telegram_chat_id_admin'])) {
            CaseSetting::set('telegram_chat_id_admin', $validated['edit_request_telegram_chat_id_admin']);
        }
        if (isset($validated['edit_request_telegram_chat_id_finance'])) {
            CaseSetting::set('telegram_chat_id_finance', $validated['edit_request_telegram_chat_id_finance']);
        }
        if (isset($validated['edit_request_telegram_chat_id_fb'])) {
            CaseSetting::set('telegram_chat_id_fb', $validated['edit_request_telegram_chat_id_fb']);
        }
        if (isset($validated['edit_request_telegram_chat_id_onhand_delete'])) {
            CaseSetting::set('telegram_chat_id_onhand_delete', $validated['edit_request_telegram_chat_id_onhand_delete']);
        }

        return back()->with('success', 'Separated Telegram and System configurations saved successfully.');
    }

    /**
     * Send an interactive test notification to confirm Telegram connectivity.
     */
    public function testTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'channel_name' => 'nullable|string',
            'token_type' => 'nullable|string|in:edit_request,case',
        ]);

        $chatId = trim($validated['chat_id']);
        $channelName = $validated['channel_name'] ?? 'Target Channel';
        $tokenType = $validated['token_type'] ?? 'edit_request';
        $actor = $request->user()?->name ?? 'System Admin';
        $timestamp = now()->format('Y-m-d H:i:s');

        $token = ($tokenType === 'case')
            ? TelegramService::getCaseToken()
            : TelegramService::getEditRequestToken();

        $moduleLabel = ($tokenType === 'case') ? 'IT Case Management Bot' : 'Edit & Void Requests Bot';

        $message = "<b>🔔 TELEGRAM CONNECTION TEST</b>\n\n" .
            "<b>Module:</b> " . TelegramService::escape($moduleLabel) . "\n" .
            "<b>Channel:</b> " . TelegramService::escape($channelName) . "\n" .
            "<b>Chat ID:</b> <code>" . TelegramService::escape($chatId) . "</code>\n" .
            "<b>Initiated By:</b> " . TelegramService::escape($actor) . "\n" .
            "<b>Time:</b> {$timestamp}\n\n" .
            "✅ <i>If you received this message, this bot token is working and active!</i>";

        $success = TelegramService::sendMessage($chatId, $message, $token);

        if ($success) {
            return back()->with('success', "Test message delivered successfully to {$channelName} ({$chatId}) via {$moduleLabel}!");
        }

        return back()->with('error', "Failed to send test message to {$chatId}. Verify the Bot Token, Chat ID, and bot group membership.");
    }
}
