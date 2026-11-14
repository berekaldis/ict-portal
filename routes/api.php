<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['status' => 'ok']));

Route::post('/telegram/kaldis-communication', [TelegramWebhookController::class, 'handleKaldisCommunication']);
Route::post('/telegram/webhook/{slug}', [TelegramWebhookController::class, 'handleDynamicWebhook']);
