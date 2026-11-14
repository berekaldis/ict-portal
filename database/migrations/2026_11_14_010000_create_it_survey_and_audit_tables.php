<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Survey Categories
        if (!Schema::hasTable('it_survey_categories')) {
            Schema::create('it_survey_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('icon', 50)->default('fas fa-desktop');
                $table->enum('type', ['hardware', 'software'])->default('hardware');
                $table->boolean('has_photo')->default(true);
                $table->text('description')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Survey Checklist Items
        if (!Schema::hasTable('it_survey_checklist_items')) {
            Schema::create('it_survey_checklist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('it_survey_categories')->onDelete('cascade');
                $table->text('question');
                $table->text('help_text')->nullable();
                $table->integer('display_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Hardware Assets Inventory
        if (!Schema::hasTable('it_hardware_assets')) {
            Schema::create('it_hardware_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 150)->nullable();
                $table->string('asset_tag', 100)->nullable()->index();
                $table->string('serial_number', 100)->nullable()->index();
                $table->string('asset_type', 100)->default('pos'); // pos, printer, switch, router, ups, cctv, computer, scanner, server, other
                $table->string('brand', 100)->nullable();
                $table->string('model', 100)->nullable();
                $table->text('specifications')->nullable();
                $table->date('purchase_date')->nullable();
                $table->date('warranty_expiry')->nullable();
                $table->enum('status', ['operational', 'maintenance', 'repair', 'retired', 'faulty', 'missing'])->default('operational');
                $table->string('assigned_to', 150)->nullable();
                $table->string('location', 150)->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->text('notes')->nullable();
                $table->string('photo_path', 255)->nullable();
                $table->timestamps();
            });
        }

        // 4. IT Survey Inspection Reports
        if (!Schema::hasTable('it_survey_reports')) {
            Schema::create('it_survey_reports', function (Blueprint $table) {
                $table->id();
                $table->string('report_uuid', 64)->unique();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 150);
                $table->string('technician_name', 150)->nullable();
                $table->unsignedBigInteger('technician_id')->nullable();
                $table->string('branch_manager', 150);
                $table->enum('type', ['hardware', 'software'])->default('hardware');
                $table->integer('health_score')->default(100);
                $table->string('health_status', 50)->default('Good'); // Good, Warning, Critical
                $table->integer('critical_count')->default(0);
                $table->integer('total_items')->default(0);
                $table->integer('passed_items')->default(0);
                $table->integer('failed_items')->default(0);
                $table->integer('skipped_items')->default(0);
                $table->json('checklist_data')->nullable(); // detailed evaluations with status, notes, skip_reason
                $table->text('issues_summary')->nullable();
                $table->text('actions_taken')->nullable();
                $table->text('recommendations')->nullable();
                $table->json('photos')->nullable();
                $table->timestamp('submitted_at')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('it_survey_reports');
        Schema::dropIfExists('it_hardware_assets');
        Schema::dropIfExists('it_survey_checklist_items');
        Schema::dropIfExists('it_survey_categories');
    }
};
