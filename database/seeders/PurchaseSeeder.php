<?php

namespace Database\Seeders;

use App\Models\Purchase\Purchase;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receipts = Purchase::factory()
            ->count(20)
            ->hasItems(rand(1, 4))
            ->create();

        foreach ($receipts as $receipt) {
            Purchase::updateTotal($receipt);
        }
    }
}
