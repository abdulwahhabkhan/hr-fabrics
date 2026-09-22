<?php

namespace Database\Seeders;

use App\Models\Stock\Conversion;
use Illuminate\Database\Seeder;

class ConversionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Conversion::factory()->count(20)->create();
    }
}
