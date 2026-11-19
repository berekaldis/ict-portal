<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:test-edit-routing', function () {
    $this->info('Testing Telegram routing for Edit Requests...');

    $financeTest = [
        'request_id' => 'TEST-FIN-' . date('Ymd-His'),
        'branch' => 'Bole Medhanialem',
        'user_name' => 'Finance Test User',
        'voucher_code' => 'TEST-VOUCHER-01',
        'voucher_type' => 'Cash Receipt',
        'action_type' => 'Edit',
        'edit_type' => 'Date Change',
        'reason' => 'Diagnostic test message to verify Finance channel reception',
        'target_dept' => 'Finance',
        'status' => 'Pending',
    ];

    $this->info('1. Sending test to Finance...');
    $resFinance = \App\Services\TelegramService::notifyEditRequest($financeTest, 'NEW_REQUEST', 'System Test');
    $this->line($resFinance ? '   [OK] Delivered to Finance Group (-1003609053132)' : '   [FAILED] Failed to send to Finance');

    $fbTest = [
        'request_id' => 'TEST-FB-' . date('Ymd-His'),
        'branch' => 'Meskel Flower',
        'user_name' => 'F&B Test User',
        'voucher_code' => 'TEST-VOUCHER-02',
        'voucher_type' => 'Sales Voucher',
        'action_type' => 'Edit',
        'edit_type' => 'Item Correction',
        'reason' => 'Diagnostic test message to verify F&B channel reception',
        'target_dept' => 'F&B',
        'status' => 'Pending',
    ];

    $this->info('2. Sending test to F&B...');
    $resFb = \App\Services\TelegramService::notifyEditRequest($fbTest, 'NEW_REQUEST', 'System Test');
    $this->line($resFb ? '   [OK] Delivered to F&B Group (-1003821366530)' : '   [FAILED] Failed to send to F&B');

    $itTest = [
        'request_id' => 'TEST-IT-' . date('Ymd-His'),
        'branch' => 'Sarbet',
        'user_name' => 'IT Execution User',
        'voucher_code' => 'TEST-VOUCHER-03',
        'voucher_type' => 'Store Issue',
        'action_type' => 'Edit',
        'edit_type' => 'Approval Execution',
        'reason' => 'Diagnostic test message to verify IT Admin group reception upon Approval',
        'target_dept' => 'Finance',
        'status' => 'Approved',
    ];

    $this->info('3. Sending test to IT Admin (Approved state)...');
    $resIt = \App\Services\TelegramService::notifyEditRequest($itTest, 'Approved', 'Manager Approver');
    $this->line($resIt ? '   [OK] Delivered to IT Group (-1001502645326)' : '   [FAILED] Failed to send to IT');
})->purpose('Send test notifications to Finance, F&B, and IT groups');

// Automated sync from Company System database: runs hourly
Schedule::command('sync:company-system')->hourly()->withoutOverlapping();
