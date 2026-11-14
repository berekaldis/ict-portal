<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->id();
                $table->string('branch', 150)->default('Bole Medhanialem')->index();
                $table->string('table_number', 50)->nullable();
                $table->string('q1_frequency', 100)->nullable();
                $table->string('q2_reason', 150)->nullable();
                $table->string('q2_reason_other', 255)->nullable();
                $table->string('q3_spend', 100)->nullable();
                $table->text('q4_orders')->nullable();
                $table->string('q4_orders_other', 255)->nullable();
                $table->string('q5_missing_menu', 50)->nullable();
                $table->text('q5_missing_detail')->nullable();
                $table->text('q6_factors')->nullable();
                $table->string('q6_factors_other', 255)->nullable();
                $table->string('q7_location_rating', 100)->nullable();
                $table->string('q8_price_rating', 100)->nullable();
                $table->text('q9_improvements')->nullable();
                $table->string('q9_improvements_other', 255)->nullable();
                $table->string('q10_consider_other_cafe', 50)->nullable();
                $table->string('q11_why_other_cafe', 150)->nullable();
                $table->string('q11_why_other_other', 255)->nullable();
                $table->string('q12_if_branch_closed', 150)->nullable();
                $table->string('q12_if_branch_closed_other', 255)->nullable();
                $table->string('q13_importance', 100)->nullable();
                $table->string('q14_waiter_rating', 100)->nullable();
                $table->text('q15_waiter_improvements')->nullable();
                $table->string('q15_waiter_improvements_other', 255)->nullable();
                $table->string('q16_interior_rating', 100)->nullable();
                $table->text('q17_interior_improvements')->nullable();
                $table->string('q17_interior_improvements_other', 255)->nullable();
                $table->text('q18_waiter_recommendations')->nullable();
                $table->text('q19_service_suggestions')->nullable();
                $table->text('q20_visit_motivation')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
