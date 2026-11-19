<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telecom_phone_numbers')) {
            Schema::table('telecom_phone_numbers', function (Blueprint $table) {
                if (!Schema::hasColumn('telecom_phone_numbers', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('notes');
                }
            });
        }

        if (Schema::hasTable('telecom_broadbands')) {
            Schema::table('telecom_broadbands', function (Blueprint $table) {
                if (!Schema::hasColumn('telecom_broadbands', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telecom_phone_numbers')) {
            Schema::table('telecom_phone_numbers', function (Blueprint $table) {
                if (Schema::hasColumn('telecom_phone_numbers', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }
            });
        }

        if (Schema::hasTable('telecom_broadbands')) {
            Schema::table('telecom_broadbands', function (Blueprint $table) {
                if (Schema::hasColumn('telecom_broadbands', 'attachment_path')) {
                    $table->dropColumn('attachment_path');
                }
            });
        }
    }
};
