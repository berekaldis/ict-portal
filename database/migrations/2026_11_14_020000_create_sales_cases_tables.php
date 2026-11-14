<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sales Cases Sequence (for generating CASE-0001-FIN)
        if (!Schema::hasTable('sales_case_sequence')) {
            Schema::create('sales_case_sequence', function (Blueprint $table) {
                $table->id();
                $table->integer('case_number')->default(0);
                $table->timestamps();
            });
        }

        // 2. Sales Cases
        if (!Schema::hasTable('sales_cases')) {
            Schema::create('sales_cases', function (Blueprint $table) {
                $table->id();
                $table->string('case_id', 50)->unique()->index();
                $table->date('date')->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 150)->nullable();
                $table->decimal('z_report', 15, 2)->default(0.00);
                $table->decimal('cash_sales', 15, 2)->default(0.00);
                $table->decimal('difference', 15, 2)->default(0.00)->index();
                $table->enum('progress', ['Not Start', 'In Progress', 'Escalated', 'Done'])->default('Not Start')->index();
                $table->text('remark')->nullable();
                $table->string('submitted_by', 150)->nullable();
                $table->string('technical_name', 150)->nullable();
                $table->string('escalated_to', 150)->nullable();
                $table->timestamp('escalation_date')->nullable();
                $table->timestamp('case_fixed_date')->nullable();
                $table->boolean('approved')->default(false)->index();
                $table->string('approved_by', 150)->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->string('attachment_file', 255)->nullable();
                $table->string('attachment_name', 255)->nullable();
                $table->string('attachment_type', 50)->default('file');
                $table->json('attachments')->nullable();
                $table->timestamps();
            });
        }

        // 3. Sales Case Attachments
        if (!Schema::hasTable('sales_case_attachments')) {
            Schema::create('sales_case_attachments', function (Blueprint $table) {
                $table->id();
                $table->string('case_id', 50)->index();
                $table->string('file_path', 255);
                $table->string('file_name', 255);
                $table->string('file_type', 100)->nullable();
                $table->integer('file_size')->default(0);
                $table->string('uploaded_by', 150)->nullable();
                $table->timestamp('uploaded_at')->useCurrent();
            });
        }

        // 4. Sales Case Comments
        if (!Schema::hasTable('sales_case_comments')) {
            Schema::create('sales_case_comments', function (Blueprint $table) {
                $table->id();
                $table->string('case_id', 50)->index();
                $table->string('username', 150);
                $table->text('comment');
                $table->timestamps();
            });
        }

        // 5. Sales Case History (Audit trail)
        if (!Schema::hasTable('sales_case_history')) {
            Schema::create('sales_case_history', function (Blueprint $table) {
                $table->id();
                $table->string('case_id', 50)->nullable()->index();
                $table->string('username', 150);
                $table->string('action', 100);
                $table->text('details')->nullable();
                $table->timestamps();
            });
        }

        // 6. Sales Settings (Telegram destination channels, etc.)
        if (!Schema::hasTable('sales_settings')) {
            Schema::create('sales_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_settings');
        Schema::dropIfExists('sales_case_history');
        Schema::dropIfExists('sales_case_comments');
        Schema::dropIfExists('sales_case_attachments');
        Schema::dropIfExists('sales_cases');
        Schema::dropIfExists('sales_case_sequence');
    }
};
