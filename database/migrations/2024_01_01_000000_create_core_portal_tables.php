<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 191)->unique();
                $table->string('name', 191);
                $table->string('full_name', 150)->nullable();
                $table->string('email', 191)->nullable()->index();
                $table->string('password', 191);
                $table->string('role', 191)->default('User');
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('must_change_password')->default(true);
                $table->string('department', 191)->nullable()->index();
                $table->string('branch', 191)->nullable()->index();
                $table->string('telegram_chat_id', 191)->nullable();
                $table->string('telegram_username', 191)->nullable();
                $table->string('phone_number', 50)->nullable();
                $table->string('chat_id', 50)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 2. Branches
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('branch_name', 191)->unique();
                $table->boolean('is_active')->default(true);
                $table->string('telegram_chat_id', 100)->nullable();
                $table->timestamps();
            });
        }

        // 3. Edit Department
        if (!Schema::hasTable('edit_department')) {
            Schema::create('edit_department', function (Blueprint $table) {
                $table->increments('id');
                $table->string('dept_name', 100)->unique();
                $table->string('telegram_chat_id', 50)->nullable();
            });
        }

        // 4. Edit Request Usernames
        if (!Schema::hasTable('edit_request_username')) {
            Schema::create('edit_request_username', function (Blueprint $table) {
                $table->increments('id');
                $table->string('user_name', 150)->unique();
                $table->string('branch', 200)->nullable();
                $table->unsignedInteger('request_count')->default(1);
                $table->dateTime('first_seen');
                $table->dateTime('last_seen');
            });
        }

        // 5. Edit Requests
        if (!Schema::hasTable('edit_requests')) {
            Schema::create('edit_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('request_id', 50)->unique();
                $table->dateTime('timestamp')->index();
                $table->string('branch', 200)->index();
                $table->string('user_name', 100);
                $table->string('store_user', 150)->nullable();
                $table->string('voucher_code', 100)->nullable();
                $table->string('voucher_type', 100)->nullable();
                $table->string('action_type', 100)->index();
                $table->string('edit_type', 100)->nullable();
                $table->text('reason')->nullable();
                $table->string('target_dept', 500)->index();
                $table->string('incorrect_date', 50)->nullable();
                $table->string('correct_date', 50)->nullable();
                $table->string('shift', 50)->nullable();
                $table->string('status', 50)->default('Pending')->index();
                $table->string('approver', 200)->nullable();
                $table->string('telegram_msg_id', 100)->nullable();
            });
        }

        // 6. Edit System Logs
        if (!Schema::hasTable('edit_system_logs')) {
            Schema::create('edit_system_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('timestamp')->index();
                $table->string('action', 100);
                $table->string('actor', 100);
                $table->string('request_id', 50)->nullable();
            });
        }

        // 7. Case Types
        if (!Schema::hasTable('case_types')) {
            Schema::create('case_types', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->unique();
            });
        }

        // 8. Case Settings
        if (!Schema::hasTable('case_settings')) {
            Schema::create('case_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
            });
        }

        // 9. Case Cases
        if (!Schema::hasTable('case_cases')) {
            Schema::create('case_cases', function (Blueprint $table) {
                $table->increments('id');
                $table->string('case_id', 50)->unique();
                $table->string('case_type', 100)->default('Support');
                $table->text('description');
                $table->date('created_date')->index();
                $table->date('due_date')->nullable()->index();
                $table->string('status', 50)->default('Not Started')->index();
                $table->string('priority', 50)->default('Medium')->index();
                $table->string('assigned_to', 100)->nullable()->index();
                $table->string('created_by', 100);
                $table->text('remarks')->nullable();
                $table->text('attachments')->nullable();
                $table->dateTime('last_modified')->nullable();
            });
        }

        // 10. Case Comments
        if (!Schema::hasTable('case_comments')) {
            Schema::create('case_comments', function (Blueprint $table) {
                $table->increments('id');
                $table->string('case_id', 50)->index();
                $table->string('author', 100);
                $table->text('comment');
                $table->dateTime('created_at');
            });
        }

        // 11. Case History
        if (!Schema::hasTable('case_history')) {
            Schema::create('case_history', function (Blueprint $table) {
                $table->increments('id');
                $table->string('case_id', 50)->index();
                $table->string('action', 200);
                $table->text('details')->nullable();
                $table->string('user', 100);
                $table->dateTime('timestamp')->index();
            });
        }

        // 12. Case Assignees
        if (!Schema::hasTable('case_assignees')) {
            Schema::create('case_assignees', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->unique();
                $table->string('telegram_id', 100)->nullable();
                $table->string('status', 20)->default('active')->index();
            });
        }

        // 13. Case Password Resets
        if (!Schema::hasTable('case_password_resets')) {
            Schema::create('case_password_resets', function (Blueprint $table) {
                $table->increments('id');
                $table->string('username', 100)->index();
                $table->string('otp', 10);
                $table->string('token', 255)->nullable()->index();
                $table->dateTime('expires_at')->index();
                $table->dateTime('created_at')->nullable();
            });
        }

        // 14. Telegram Groups
        if (!Schema::hasTable('telegram_groups')) {
            Schema::create('telegram_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->bigInteger('telegram_chat_id')->unique();
                $table->string('title', 255);
                $table->string('username', 100)->nullable();
                $table->string('type', 32)->default('supergroup');
                $table->string('department', 100)->default('General')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->string('bot_status', 32)->default('unknown');
                $table->boolean('bot_is_admin')->default(false);
                $table->boolean('bot_can_read_all_messages')->default(false);
                $table->dateTime('last_health_check_at')->nullable();
                $table->dateTime('last_update_at')->nullable();
                $table->bigInteger('previous_chat_id')->nullable();
                $table->timestamps();
            });
        }

        // 15. Sessions
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id', 191)->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // 16. Cache
        if (!Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key', 191)->primary();
                $table->mediumText('value');
                $table->integer('expiration')->index();
            });
        }

        // 17. Jobs
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue', 191)->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        // 18. Failed Jobs
        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('uuid', 191)->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Keep empty to prevent accidental data loss in production
    }
};
