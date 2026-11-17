<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Restore all 769 active users.
     */
    public function run(): void
    {
        $this->call(FullUsersSeeder::class);
    }
}
