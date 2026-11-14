<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handleKaldisCommunication(Request $request): JsonResponse
    {
        try {
            $update = $request->all();
            if (!empty($update)) {
                $configPath = base_path('telegramgroup_mgt/config.json');
                $dbPath = base_path('telegramgroup_mgt/kaldis.db');
                if (file_exists($configPath)) {
                    require_once base_path('telegramgroup_mgt/src/bootstrap.php');

                    $config = \KaldisTelegram\BotConfig::fromFile($configPath);
                    $storage = new \KaldisTelegram\SQLiteStorage($dbPath);
                    $client = new \KaldisTelegram\TelegramClient($config->botToken);
                    $bot = new \KaldisTelegram\KaldisBot($config, $storage, $client);
                    $bot->handleUpdate($update);
                }
            }
        } catch (\Throwable $e) {
            Log::error("Telegram Kaldis communication webhook error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        return response()->json(['status' => 'ok']);
    }

    public function handleDynamicWebhook(string $slug, Request $request): JsonResponse
    {
        $cleanSlug = strtolower(trim($slug));
        if (in_array($cleanSlug, ['kaldis', 'kaldis-communication', 'communication', 'kaldis-bot'], true)) {
            return $this->handleKaldisCommunication($request);
        }

        return response()->json(['status' => 'ok']);
    }
}
