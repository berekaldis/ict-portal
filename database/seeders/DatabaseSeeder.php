<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Restores only genuine live production data from kaldis_full_backup.sql.
     */
    public function run(): void
    {
        $this->command->info('Restoring live database data from kaldis_full_backup.sql...');
        $this->command->call('db:restore-data', ['--force' => true]);
    }
}
