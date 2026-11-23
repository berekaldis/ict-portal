<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create password_entries table
        if (!Schema::hasTable('password_entries')) {
            Schema::create('password_entries', function (Blueprint $table) {
                $table->id();
                $table->string('title', 150);
                $table->string('category', 100)->default('Other');
                $table->string('device_type', 100)->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->integer('port')->nullable();
                $table->text('link_url')->nullable();
                $table->string('protocol', 50)->nullable()->default('HTTPS');
                $table->string('username', 150)->nullable();
                $table->text('password'); // Stored encrypted
                $table->text('secondary_secret')->nullable(); // Encrypted PIN, 2FA, enable secret
                $table->string('auth_type', 50)->default('Password');
                $table->string('branch_name', 150)->nullable();
                $table->string('environment', 50)->default('Production');
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamp('last_revealed_at')->nullable();
                $table->unsignedBigInteger('last_revealed_by')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('last_revealed_by')->references('id')->on('users')->nullOnDelete();

                $table->index('category');
                $table->index('branch_name');
                $table->index('ip_address');
            });
        }

        // 2. Create password_access_logs table
        if (!Schema::hasTable('password_access_logs')) {
            Schema::create('password_access_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('entry_id');
                $table->unsignedBigInteger('user_id');
                $table->string('action', 50); // created, updated, revealed, copied, deleted, exported
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('entry_id')->references('id')->on('password_entries')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['entry_id', 'created_at']);
            });
        }

        // 3. Register permissions
        $permissions = [
            'password_manager.view' => 'View Password Manager and Device Credentials',
            'password_manager.manage' => 'Manage (Create, Edit, Delete) Credentials and Devices',
            'password_manager.reveal' => 'Reveal and Copy Cleartext Passwords and Secrets',
        ];

        $now = now();
        foreach ($permissions as $name => $desc) {
            $exists = DB::table('permissions')->where('name', $name)->exists();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Assign to Super Admin and Admin roles
        try {
            $adminRoles = DB::table('roles')->whereIn('name', [
                'Super Admin',
                'super-admin',
                'superadmin',
                'Admin',
                'admin',
                'IT Admin',
                'IT Manager',
                'IT Specialist',
                'IT Support',
            ])->get();

            $permIds = DB::table('permissions')->whereIn('name', array_keys($permissions))->pluck('id');

            foreach ($adminRoles as $role) {
                foreach ($permIds as $permId) {
                    $has = DB::table('role_has_permissions')
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permId)
                        ->exists();
                    if (!$has) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $role->id,
                            'permission_id' => $permId,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('password_access_logs');
        Schema::dropIfExists('password_entries');
        DB::table('permissions')->whereIn('name', [
            'password_manager.view',
            'password_manager.manage',
            'password_manager.reveal',
        ])->delete();
    }
};
