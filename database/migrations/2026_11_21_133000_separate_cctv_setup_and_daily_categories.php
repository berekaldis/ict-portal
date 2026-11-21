<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('it_survey_categories')) {
            // Restore any software categories that might have matched
            DB::table('it_survey_categories')
                ->where('name', 'LIKE', '%Fiscal%')
                ->update(['type' => 'software']);

            // Update CCTV 1. One-time Setup to type cctv_setup
            DB::table('it_survey_categories')
                ->where('name', 'LIKE', '%CCTV%1. One-time%')
                ->orWhere('name', 'LIKE', 'CCTV – 1.%')
                ->update([
                    'type' => 'cctv_setup',
                    'name' => 'CCTV – 1. One-Time Setup & New Camera Installation',
                    'description' => 'Security hardening, passwords, VLAN, NTP, and NVR recording verification when commissioning a branch or installing a new camera.',
                ]);

            // Update CCTV 2. Daily to type cctv_daily
            DB::table('it_survey_categories')
                ->where('name', 'LIKE', '%CCTV%2. Daily%')
                ->orWhere('name', 'LIKE', 'CCTV – 2.%')
                ->update([
                    'type' => 'cctv_daily',
                    'name' => 'CCTV – 2. Daily Health Check (Helpdesk, remote)',
                    'description' => 'Quick 5-minute morning verification: all cameras online, recording on all channels, correct time, and zero disk errors.',
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('it_survey_categories')) {
            DB::table('it_survey_categories')
                ->whereIn('type', ['cctv_setup', 'cctv_daily'])
                ->update(['type' => 'cctv']);
        }
    }
};
