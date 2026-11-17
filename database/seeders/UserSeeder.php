<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Restore all live users directly from kaldis_full_backup.sql.
     */
    public function run(): void
    {
        $this->command->info('UserSeeder: Restoring all live production users...');
        $this->command->call('db:restore-data', ['--force' => true]);
    }
}
