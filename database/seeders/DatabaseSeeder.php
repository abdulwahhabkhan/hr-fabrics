<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(BaseSeeder::class);
        $this->call(InboundSeeder::class);
        $this->call(OutboundSeeder::class);
        $this->call(AccountSeeder::class);
    }
}
