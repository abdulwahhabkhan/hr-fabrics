<?php

namespace Database\Seeders;

use App\Models\Sales\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Customer::factory()->count(1)->create([
            'name' => 'Cash',
            'phone' => '0000',
            'email' => '',
            'address' => null,
        ]);
        Customer::factory(20)->create();
    }
}
