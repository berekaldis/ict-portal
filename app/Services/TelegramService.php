<?php

namespace App\Services;

use App\Models\CaseAssignee;
use App\Models\CaseSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    /**
     * Get the dedicated Bot Token for Voucher Edit Requests & Reports.
     */
    public static function getEditRequestToken(): string
    {
        return CaseSetting::get('edit_request_telegram_bot_token')
            ?: CaseSetting::get('telegram_bot_token')
            ?: config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8'));
    }

    /**
     * Get the dedicated Bot Token for IT Case Management.
     */
    public static function getCaseToken(): string
    {
        return CaseSetting::get('case_telegram_bot_token')
            ?: CaseSetting::get('telegramBotToken')
            ?: CaseSetting::get('telegram_bot_token')
            ?: env('TELEGRAM_CASE_BOT_TOKEN', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8');
    }

    /**
     * Backward-compatible token getter.
     */
    public static function getToken(): string
    {
        return self::getEditRequestToken();
    }

    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Base sender method using a specific bot token.
     */
    public static function sendMessage(?string $chatId, string $htmlMessage, ?string $token = null, ?array $replyMarkup = null): bool
    {
        $botToken = $token ?: self::getToken();
        if (empty($botToken) || empty($chatId)) {
            Log::warning('TelegramService: missing token or chatId');
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            // Ensure cURL SSL verification never blocks delivery in shared hosting / cPanel environments
            $http = Http::timeout(10)->withoutVerifying();

            $payload = [
                'chat_id' => $chatId,
                'text' => $htmlMessage,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ];

            if (!empty($replyMarkup)) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $response = $http->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            // Retry as plain text if HTML entity parsing failed
            if ($response->status() === 400 && str_contains($response->body(), "can't parse entities")) {
                $fallbackPayload = [
                    'chat_id' => $chatId,
                    'text' => strip_tags($htmlMessage),
                    'disable_web_page_preview' => false,
                ];
                if (!empty($replyMarkup)) {
                    $fallbackPayload['reply_markup'] = $replyMarkup;
                }
                $retry = $http->post($url, $fallbackPayload);
                return $retry->successful();
            }

            Log::error('TelegramService API error: ' . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::error('TelegramService exception: ' . $e->getMessage());
            return false;
        }
    }

    // ============================================================
    // CASE MANAGEMENT TELEGRAM ALERTS (case/case_config.php)
    // ============================================================

    public static function notifyCaseCreated(array $caseData, string $actor): bool
    {
        $caseToken = self::getCaseToken();
        $chatId = CaseSetting::get('case_telegram_group_chat_id')
            ?: CaseSetting::get('telegramGroupChatId')
            ?: CaseSetting::get('telegram_chat_id_admin')
            ?: env('TELEGRAM_CHAT_ID_ADMIN', '-1001502645326');

        $priorityEmoji = match ($caseData['priority'] ?? 'Medium') {
            'Critical' => '🔴 [CRITICAL]',
            'Medium' => '🟡 [MEDIUM]',
            'Low' => '🟢 [LOW]',
            default => '⚪'
        };

        $msg = "<b>🆕 NEW IT SUPPORT CASE</b>\n\n" .
            "<b>Case ID:</b> <code>" . self::escape($caseData['case_id'] ?? '') . "</code>\n" .
            "<b>Priority:</b> {$priorityEmoji} " . self::escape($caseData['priority'] ?? '') . "\n" .
            "<b>Category:</b> " . self::escape($caseData['case_type'] ?? '') . "\n" .
            "<b>Assigned To:</b> " . self::escape($caseData['assigned_to'] ?: 'Unassigned') . "\n" .
            "<b>Created By:</b> " . self::escape($actor) . "\n" .
            "<b>Due Date:</b> " . self::escape($caseData['due_date'] ?: 'None') . "\n\n" .
            "<b>Description:</b>\n" . self::escape($caseData['description'] ?? '');

        $sent = self::sendMessage($chatId, $msg, $caseToken);

        // Notify individual assignee directly if assigned
        self::notifyAssigneeDirectly($caseData['assigned_to'] ?? '', $msg, $caseToken);

        return $sent;
    }

    public static function notifyCaseUpdated(array $caseData, string $oldStatus, string $newStatus, string $actor, ?string $remarks = null): bool
    {
        $caseToken = self::getCaseToken();
        $chatId = CaseSetting::get('case_telegram_group_chat_id')
            ?: CaseSetting::get('telegramGroupChatId')
            ?: CaseSetting::get('telegram_chat_id_admin')
            ?: env('TELEGRAM_CHAT_ID_ADMIN', '-1001502645326');

        $statusEmoji = match ($newStatus) {
            'Fixed' => '🛠️ FIXED',
            'Completed' => '✅ COMPLETED',
            'In Progress' => '⏳ IN PROGRESS',
            'Return' => '🔄 RETURNED',
            'Reject' => '❌ REJECTED',
            default => 'ℹ️ ' . strtoupper($newStatus)
        };

        $msg = "<b>📢 CASE STATUS UPDATE</b>\n\n" .
            "<b>Case ID:</b> <code>" . self::escape($caseData['case_id'] ?? '') . "</code>\n" .
            "<b>Status:</b> {$oldStatus} ➡️ <b>{$statusEmoji}</b>\n" .
            "<b>Updated By:</b> " . self::escape($actor) . "\n" .
            "<b>Assigned To:</b> " . self::escape($caseData['assigned_to'] ?: 'Unassigned') . "\n";

        if ($remarks) {
            $msg .= "\n<b>Remarks:</b>\n" . self::escape($remarks);
        }

        $sent = self::sendMessage($chatId, $msg, $caseToken);

        self::notifyAssigneeDirectly($caseData['assigned_to'] ?? '', $msg, $caseToken);

        return $sent;
    }

    /**
     * Direct notification to individual assignee Telegram ID.
     */
    protected static function notifyAssigneeDirectly(?string $assigneeName, string $message, string $caseToken): void
    {
        if (empty($assigneeName)) return;

        try {
            $assignee = CaseAssignee::where('name', $assigneeName)
                ->where('status', 'active')
                ->whereNotNull('telegram_id')
                ->where('telegram_id', '!=', '')
                ->first();

            if ($assignee && !empty($assignee->telegram_id)) {
                self::sendMessage($assignee->telegram_id, $message, $caseToken);
            }
        } catch (\Throwable $e) {
            Log::warning('TelegramService::notifyAssigneeDirectly: ' . $e->getMessage());
        }
    }

    // ============================================================
    // EDIT & VOID REQUESTS TELEGRAM ALERTS (edit_request/config.php)
    // ============================================================

    public static function notifyEditRequest(array $requestData, string $status, string $actor = 'System'): bool
    {
        $token = self::getEditRequestToken();
        $actionType = $requestData['action_type'] ?? 'Edit';
        $targetDept = $requestData['target_dept'] ?? 'Finance';
        $isOnHandDelete = ($actionType === 'On Hand Delete');

        // Dedicated Edit Request Chat ID Routing from database settings
        $adminChatId = CaseSetting::get('edit_request_telegram_chat_id_admin')
            ?: CaseSetting::get('telegram_chat_id_admin')
            ?: '-1001502645326';

        $financeChatId = CaseSetting::get('edit_request_telegram_chat_id_finance')
            ?: CaseSetting::get('telegram_chat_id_finance')
            ?: '-1003609053132';

        $fbChatId = CaseSetting::get('edit_request_telegram_chat_id_fb')
            ?: CaseSetting::get('telegram_chat_id_fb')
            ?: '-1003821366530';

        $onHandDeleteChatId = CaseSetting::get('edit_request_telegram_chat_id_onhand_delete')
            ?: CaseSetting::get('telegram_chat_id_onhand_delete')
            ?: '-1002426639974';

        // Determine target Chat ID based on workflow requirements:
        // 1. New/Pending requests go ONLY to Finance or F&B (approvers). IT never receives new requests!
        // 2. Once Approved by Finance/F&B, request is dispatched to IT Department ($adminChatId) for technical execution!
        // 3. Once Done or Rejected, notifications return to originating department.
        $isFinanceTarget = str_contains(strtolower($targetDept), 'finance');
        $isFbTarget = str_contains(strtolower($targetDept), 'f&b') || str_contains(strtolower($targetDept), 'fb') || $isOnHandDelete;

        if ($isOnHandDelete) {
            if ($status === 'Pending' || $status === 'NEW_REQUEST') {
                $chatId = $fbChatId; // Step 1: New On Hand Delete -> F&B Group only
            } elseif ($status === 'Approved') {
                $chatId = $adminChatId; // Step 2: F&B Approved -> Sent to IT Group for Execution
            } elseif ($status === 'Done' || $status === 'Rejected') {
                $chatId = $onHandDeleteChatId ?: $fbChatId; // Step 3: Done/Rejected -> OnHand/F&B Group
            } else {
                $chatId = $fbChatId;
            }
        } else {
            // Standard Voucher Edit / Void Requests
            if ($status === 'Pending' || $status === 'NEW_REQUEST') {
                // New requests go strictly to approving department (Finance or F&B). Never to IT!
                $chatId = $isFbTarget ? $fbChatId : $financeChatId;
            } elseif ($status === 'Approved') {
                // Approved by Finance or F&B -> Send to IT Department for technical execution!
                $chatId = $adminChatId;
            } elseif ($status === 'Done') {
                // IT marked Done -> Send notification back to approving department (Finance or F&B)
                $chatId = $isFbTarget ? $fbChatId : $financeChatId;
            } elseif ($status === 'Rejected') {
                // Rejected -> Return notification to approving department
                $chatId = $isFbTarget ? $fbChatId : $financeChatId;
            } else {
                $chatId = $isFbTarget ? $fbChatId : $financeChatId;
            }
        }

        $header = match ($status) {
            'NEW_REQUEST', 'Pending' => $isOnHandDelete ? "<b>🔥 NEW ON HAND DELETE REQUEST</b>\nTarget: F&B Department" : "<b>📝 NEW VOUCHER EDIT/VOID REQUEST</b>\nTarget: " . ($isFbTarget ? "F&B Department" : "Finance Department"),
            'Approved' => $isOnHandDelete
                ? "<b>✅ ON HAND DELETE APPROVED</b>\nApproved By: " . self::escape($actor) . "\n➡️ Sent to IT Group for Execution"
                : "<b>✅ VOUCHER REQUEST APPROVED</b>\nApproved By: " . self::escape($actor) . "\n➡️ Sent to IT Department for Execution",
            'Rejected' => "<b>❌ REQUEST REJECTED</b>\nBy: " . self::escape($actor),
            'Done' => $isOnHandDelete
                ? "<b>🎉 ON HAND DELETE COMPLETED ✅</b>\nProcessed By IT: " . self::escape($actor) . "\n➡️ Notification sent to F&B Group"
                : "<b>🎉 VOUCHER REQUEST COMPLETED ✅</b>\nProcessed By IT: " . self::escape($actor) . "\n➡️ Notification sent to " . ($isFbTarget ? "F&B Department" : "Finance Department"),
            default => "<b>REQUEST UPDATE ({$status})</b>"
        };

        $msg = $header . "\n\n" .
            "<b>Request ID:</b> <code>" . self::escape($requestData['request_id'] ?? '') . "</code>\n" .
            "<b>Branch:</b> " . self::escape($requestData['branch'] ?? '') . "\n" .
            "<b>User:</b> " . self::escape($requestData['user_name'] ?? '') . "\n" .
            "<b>Action:</b> " . self::escape($actionType) . "\n";

        if ($isOnHandDelete) {
            if (!empty($requestData['store_user'])) {
                $msg .= "<b>Store User:</b> " . self::escape($requestData['store_user']) . "\n";
            }
            $msg .= "<b>Shift:</b> " . self::escape($requestData['shift'] ?: 'Shift 1') . "\n";
            $msg .= "<b>Delete Date:</b> " . self::escape($requestData['correct_date'] ?: '-') . "\n";
        } else {
            $msg .= "<b>Voucher Code:</b> <code>" . self::escape($requestData['voucher_code'] ?? '') . "</code>\n" .
                "<b>Voucher Type:</b> " . self::escape($requestData['voucher_type'] ?? '') . "\n";
            if (!empty($requestData['edit_type'])) {
                $msg .= "<b>Edit Type:</b> " . self::escape($requestData['edit_type']) . "\n";
            }
            if (!empty($requestData['incorrect_date']) || !empty($requestData['correct_date'])) {
                $msg .= "<b>Date Change:</b> " . self::escape($requestData['incorrect_date'] ?: '-') . " ➡️ " . self::escape($requestData['correct_date'] ?: '-') . "\n";
            }
        }

        $exactReqId = trim((string)($requestData['request_id'] ?? ''));
        $targetId = $exactReqId ?: ($requestData['id'] ?? '');
        $dashboardUrl = 'https://ict-portal.kaldisbunnaet.com/edit-requests/' . rawurlencode((string)$targetId);

        $msg .= "<b>Department:</b> " . self::escape($targetDept) . "\n" .
            "<b>Reason:</b> " . self::escape($requestData['reason'] ?? '') . "\n" .
            "<b>Status:</b> <b>" . self::escape($requestData['status'] ?? $status) . "</b>\n\n" .
            "<b>Dashboard:</b> <a href=\"{$dashboardUrl}\">Open Dashboard</a>\n";

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 Open Dashboard (' . ($exactReqId ?: $targetId) . ')', 'url' => $dashboardUrl],
                ],
            ],
        ];

        return self::sendMessage($chatId, $msg, $token, $replyMarkup);
    }
}
