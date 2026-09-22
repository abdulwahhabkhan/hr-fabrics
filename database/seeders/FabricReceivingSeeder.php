<?php

namespace Database\Seeders;

use App\Models\Purchase\FabricReceiving;
use Illuminate\Database\Seeder;

class FabricReceivingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $receipts = FabricReceiving::factory()
            ->count(20)
            ->hasItems(rand(1, 4))
            ->create();

        foreach ($receipts as $receipt) {
            FabricReceiving::updateTotal($receipt);
        }
    }
}
