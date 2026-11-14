<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_settings')) {
            Schema::create('hr_settings', function (Blueprint $table) {
                $table->id();
                $table->string('type', 50)->index(); // 'branch', 'job', 'rank', 'logo'
                $table->string('value', 255);
                $table->string('chat_id', 100)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_internship_requests')) {
            Schema::create('hr_internship_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_id', 50)->unique();
                $table->string('name', 150);
                $table->string('gender', 20);
                $table->string('to_branch', 100)->index();
                $table->string('work_branch', 100)->index();
                $table->string('job', 100)->index();
                $table->date('start_date');
                $table->string('pdf_path', 500)->nullable();
                $table->string('reference_status', 50)->default('Pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_qualifications')) {
            Schema::create('hr_qualifications', function (Blueprint $table) {
                $table->id();
                $table->string('request_id', 50)->unique();
                $table->string('name', 150);
                $table->string('gender', 20);
                $table->string('branch', 100)->index();
                $table->string('job', 100)->index();
                $table->date('probation_start');
                $table->string('manager_name', 150)->nullable();
                $table->string('manager_rank', 100)->nullable();
                $table->string('destination', 150)->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->string('reference_status', 50)->default('Pending');
                $table->string('internship_ref_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_recruitments')) {
            Schema::create('hr_recruitments', function (Blueprint $table) {
                $table->id();
                $table->string('request_id', 50)->unique();
                $table->string('name', 150);
                $table->string('gender', 20);
                $table->string('branch', 100)->index();
                $table->string('requested_job', 100);
                $table->string('assigned_job', 100);
                $table->date('start_date');
                $table->string('destination', 150)->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->string('qualification_ref_id', 50)->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_system_config')) {
            Schema::create('hr_system_config', function (Blueprint $table) {
                $table->string('config_key', 100)->primary();
                $table->text('config_value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_recruitments');
        Schema::dropIfExists('hr_qualifications');
        Schema::dropIfExists('hr_internship_requests');
        Schema::dropIfExists('hr_settings');
        Schema::dropIfExists('hr_system_config');
    }
};
