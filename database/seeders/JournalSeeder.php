<?php

namespace Database\Seeders;

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = Account::factory()->count(10)->create();
        foreach ($accounts as $account) {
            $journal = Journal::factory()->create([
                'detail' => 'faker account',
            ]);

            $journal->transactions();
        }
    }
}
