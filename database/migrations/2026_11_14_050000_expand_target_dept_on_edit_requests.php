<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('edit_requests')) {
            DB::statement("ALTER TABLE edit_requests MODIFY target_dept VARCHAR(500) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('edit_requests')) {
            DB::statement("ALTER TABLE edit_requests MODIFY target_dept VARCHAR(200) NOT NULL");
        }
    }
};
