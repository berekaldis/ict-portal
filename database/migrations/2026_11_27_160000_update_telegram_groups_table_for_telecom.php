<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_groups', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('telegram_groups', 'chat_id')) {
                $table->string('chat_id', 100)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('telegram_groups', 'group_type')) {
                $table->string('group_type', 50)->default('Supergroup')->after('username');
            }
            if (!Schema::hasColumn('telegram_groups', 'category')) {
                $table->string('category', 100)->nullable()->after('group_type');
            }
            if (!Schema::hasColumn('telegram_groups', 'purpose')) {
                $table->string('purpose', 255)->nullable()->after('category');
            }
            if (!Schema::hasColumn('telegram_groups', 'description')) {
                $table->text('description')->nullable()->after('purpose');
            }
            if (!Schema::hasColumn('telegram_groups', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('description');
            }
            if (!Schema::hasColumn('telegram_groups', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('telegram_groups', 'managed_by')) {
                $table->string('managed_by', 255)->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('telegram_groups', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('managed_by');
            }
            if (!Schema::hasColumn('telegram_groups', 'status')) {
                $table->string('status', 50)->default('Active')->after('created_by');
            }
            if (!Schema::hasColumn('telegram_groups', 'invite_link')) {
                $table->string('invite_link', 500)->nullable()->after('status');
            }
            if (!Schema::hasColumn('telegram_groups', 'qr_code_path')) {
                $table->string('qr_code_path', 255)->nullable()->after('invite_link');
            }
            if (!Schema::hasColumn('telegram_groups', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('qr_code_path');
            }
            if (!Schema::hasColumn('telegram_groups', 'member_count')) {
                $table->integer('member_count')->nullable()->after('is_public');
            }
            if (!Schema::hasColumn('telegram_groups', 'link_generated_at')) {
                $table->dateTime('link_generated_at')->nullable()->after('member_count');
            }
            if (!Schema::hasColumn('telegram_groups', 'notes')) {
                $table->text('notes')->nullable()->after('link_generated_at');
            }
            if (!Schema::hasColumn('telegram_groups', 'ticket_id')) {
                $table->unsignedBigInteger('ticket_id')->nullable()->after('notes');
            }
        });

        // Make legacy columns nullable if present
        try {
            Schema::table('telegram_groups', function (Blueprint $table) {
                if (Schema::hasColumn('telegram_groups', 'title')) {
                    $table->string('title')->nullable()->change();
                }
                if (Schema::hasColumn('telegram_groups', 'department')) {
                    $table->string('department')->nullable()->change();
                }
                if (Schema::hasColumn('telegram_groups', 'telegram_chat_id')) {
                    $table->bigInteger('telegram_chat_id')->nullable()->change();
                }
            });
        } catch (\Throwable $e) {
            // Ignore if change is unsupported by driver or already nullable
        }
    }

    public function down(): void
    {
        // Safe to keep columns
    }
};
