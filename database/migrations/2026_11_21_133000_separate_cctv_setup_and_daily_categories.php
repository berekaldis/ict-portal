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
                ->update(['type' => 'cctv_setup']);

            // Update CCTV 2. Daily to type cctv_daily
            DB::table('it_survey_categories')
                ->where('name', 'LIKE', '%CCTV%2. Daily%')
                ->orWhere('name', 'LIKE', 'CCTV – 2.%')
                ->update(['type' => 'cctv_daily']);
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
