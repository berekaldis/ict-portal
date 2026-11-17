<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Dummy user seed disabled. Live database data is preserved directly.
     */
    public function run(): void
    {
        $this->command->info('UserSeeder: Dummy seeds removed. Preserving live production users.');
    }
}
