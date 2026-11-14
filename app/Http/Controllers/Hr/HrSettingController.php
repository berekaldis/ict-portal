<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrSetting;
use App\Models\HrSystemConfig;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrSettingController extends Controller
{
    public function index(): Response
    {
        $settings = HrSetting::orderBy('type')->orderBy('value')->get();
        $botToken = HrSystemConfig::get('bot_token', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8');

        $grouped = [
            'branches' => $settings->where('type', 'branch')->values(),
            'jobs' => $settings->where('type', 'job')->values(),
            'ranks' => $settings->where('type', 'rank')->values(),
        ];

        return Inertia::render('hr/settings', [
            'grouped' => $grouped,
            'botToken' => $botToken,
        ]);
    }

    public function storeSetting(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:branch,job,rank',
            'value' => 'required|string|max:150',
            'chat_id' => 'nullable|string|max:100',
        ]);

        HrSetting::create($validated);

        return back()->with('success', ucfirst($validated['type']) . " '{$validated['value']}' added successfully.");
    }

    public function updateBranchChat(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'chat_id' => 'nullable|string|max:100',
        ]);

        $setting = HrSetting::findOrFail($id);
        $setting->update(['chat_id' => $validated['chat_id']]);

        return back()->with('success', "Chat ID updated for {$setting->value}.");
    }

    public function destroySetting($id): RedirectResponse
    {
        $setting = HrSetting::findOrFail($id);
        $setting->delete();

        return back()->with('success', "Setting removed.");
    }

    public function saveBotToken(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_token' => 'required|string|max:255',
        ]);

        HrSystemConfig::set('bot_token', $validated['bot_token']);

        return back()->with('success', "HR Telegram Bot Token updated successfully.");
    }

    public function testTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'chat_id' => 'required|string',
            'branch' => 'nullable|string',
        ]);

        $chatId = trim($validated['chat_id']);
        $branch = $validated['branch'] ?? 'HR Channel';
        $botToken = HrSystemConfig::get('bot_token') ?: '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8';

        $msg = "<b>🔔 HR SYSTEM TELEGRAM TEST</b>\n\n" .
            "<b>Channel:</b> " . htmlspecialchars($branch) . "\n" .
            "<b>Chat ID:</b> <code>" . htmlspecialchars($chatId) . "</code>\n" .
            "<b>Time:</b> " . now()->format('Y-m-d H:i:s') . "\n\n" .
            "✅ <i>If you received this message, the Kaldi's HR notification bot is working properly!</i>";

        $sent = TelegramService::sendMessage($chatId, $msg, $botToken);

        if ($sent) {
            return back()->with('success', "Test message delivered to {$branch} ({$chatId})!");
        }

        return back()->with('error', "Failed to deliver test message to {$chatId}. Verify the Bot Token and chat ID.");
    }
}
