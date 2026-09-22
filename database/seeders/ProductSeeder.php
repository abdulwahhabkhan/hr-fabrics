<?php

namespace Database\Seeders;

use App\Models\Catalog\Brand;
use App\Models\Catalog\Finish;
use App\Models\Catalog\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::all();
        $finishes = Finish::all();
        $users = User::all();

        Product::factory(4)
            ->recycle($users)
            ->sequence(
                ['name' => 'Platinum Plus Suit'],
                ['name' => 'PN Barfi Cotton'],
                ['name' => 'PN Darbar Latha'],
                ['name' => 'Blue Rose Cotton']
            )->create([
                'brand_id' => fn () => $brands->random()->id,
                'finish' => fn () => $finishes->random()->name,
                'is_box' => 0,
                'suit_price' => 2120,
                'unit_price' => 300,
            ]);

        Product::factory(4)
            ->recycle($users)
            ->sequence(
                ['name' => 'Platinum Plus Suit Box'],
                ['name' => 'PN Barfi Cotton Box'],
                ['name' => 'PN Darbar Latha Box'],
                ['name' => 'ETF Clock Tower Box']
            )->create([
                'brand_id' => fn () => $brands->random()->id,
                'finish' => fn () => $finishes->random()->name,
                'is_box' => 1,
                'suit_price' => 0,
                'unit_price' => 4000,
            ]);
    }
}
