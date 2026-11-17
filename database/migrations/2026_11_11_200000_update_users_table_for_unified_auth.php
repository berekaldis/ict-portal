<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 191)->default('User');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name', 150)->nullable();
            }
            if (!Schema::hasColumn('users', 'chat_id')) {
                $table->string('chat_id', 50)->nullable();
            }
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false);
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'branch')) {
                $table->string('branch', 191)->nullable();
            }
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 50)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('users', 'branch')) {
                $table->dropColumn('branch');
            }
            if (Schema::hasColumn('users', 'phone_number')) {
                $table->dropColumn('phone_number');
            }
        });
    }
};
