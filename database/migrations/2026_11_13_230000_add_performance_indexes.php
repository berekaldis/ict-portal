<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasIndex('users', ['email'])) {
                $table->index('email', 'idx_users_email');
            }
            if (!Schema::hasIndex('users', ['department'])) {
                $table->index('department', 'idx_users_department');
            }
            if (!Schema::hasIndex('users', ['branch'])) {
                $table->index('branch', 'idx_users_branch');
            }
            if (!Schema::hasIndex('users', ['is_active'])) {
                $table->index('is_active', 'idx_users_is_active');
            }
        });

        Schema::table('edit_requests', function (Blueprint $table) {
            if (!Schema::hasIndex('edit_requests', ['target_dept'])) {
                $table->index('target_dept', 'idx_edit_requests_target_dept');
            }
            if (!Schema::hasIndex('edit_requests', ['action_type'])) {
                $table->index('action_type', 'idx_edit_requests_action_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_department');
            $table->dropIndex('idx_users_branch');
            $table->dropIndex('idx_users_is_active');
        });

        Schema::table('edit_requests', function (Blueprint $table) {
            $table->dropIndex('idx_edit_requests_target_dept');
            $table->dropIndex('idx_edit_requests_action_type');
        });
    }
};
