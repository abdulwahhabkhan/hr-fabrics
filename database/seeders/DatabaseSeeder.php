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
        /* $this->call(FabricReceivingSeeder::class);
         $this->call(PurchaseSeeder::class);
         $this->call(CustomerSeeder::class);
         $this->call(OrderSeeder::class);*/
    }
}
