<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Account::factory()->count(10)->create(['type' => 'affiliate']);
        /*Account::factory()->count(2)
            ->sequence(fn($sequence) => ['name' => 'affiliate ' . $sequence->index + 1])
            ->create(
                [
                    'type' => 'customer',
                    'discount' => 2.5,
                    'affiliate_id' => Account::Affiliates()->get()->first()->id,
                    'commission_rate' => 1,
                ]
            );*/

    }
}
