<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('telegram_chat_id')->nullable()->after('email');
                $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            });
        }

        if (!Schema::hasColumn('branches', 'telegram_chat_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('telegram_chat_id', 100)->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['telegram_chat_id', 'telegram_username']);
            });
        }

        if (Schema::hasColumn('branches', 'telegram_chat_id')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }
};
