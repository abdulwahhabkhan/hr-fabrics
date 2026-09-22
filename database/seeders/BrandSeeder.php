<?php

namespace Database\Seeders;

use App\Models\Catalog\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\User;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        Brand::factory()->recycle($users)->create(['name' => 'Ahmad Fabrics']);
        Brand::factory()->recycle($users)->create(['name' => 'Puri Fabrics']);
        Brand::factory()->recycle($users)->create(['name' => 'Ecotex Fabrics']);
        Brand::factory()->recycle($users)->create(['name' => 'Amin Adam Fabrics']);
    }
}
