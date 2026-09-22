<?php

namespace Database\Seeders;

use App\Models\Catalog\Finish;
use Illuminate\Database\Seeder;

class FinishSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Finish::factory()->count(1)->create(['name' => 'Summer Finish']);
        Finish::factory()->count(1)->create(['name' => 'Stiff Matte']);
        Finish::factory()->count(1)->create(['name' => 'Winter Finish']);
    }
}
