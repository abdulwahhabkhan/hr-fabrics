<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Accounts\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class BaseSeeder extends Seeder
{
    private Collection $users;

    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        User::factory(1)->create([
            'email' => 'abdulwahhabkhan@hotmail.com',
            'name' => 'Abdul Khan',
            'role_id' => Role::SuperAdmin->value,
            'password' => Hash::make('test1234'),
        ]);
        User::factory()->create(['role_id' => Role::Owner->value]);
        User::factory()->create(['role_id' => Role::Manager->value]);
        $this->call(CitySeeder::class);
        $this->accountsSeeder();
        $this->catalogSeeder();
    }

    public function getUsers(): Collection
    {
        return $this->users = User::query()->get();
    }

    private function accountsSeeder(): void
    {
        $users = $this->getUsers();
        Account::factory(4)
            ->recycle($users)
            ->customer()
            ->sequence(
                ['discount' => 2],
                ['discount' => 1],
                ['discount' => 2.5],
                ['discount' => 0]
            )
            ->create();

        Account::factory()->recycle($users)
            ->agent()
            ->create(['name' => 'Kashif']);

        Account::factory(2)
            ->recycle($users)
            ->supplier()
            ->create();

        Account::factory()->recycle($users)
            ->cash()
            ->create();
    }

    private function catalogSeeder(): void
    {
        $this->call(BrandSeeder::class);
        $this->call(FinishSeeder::class);
        $this->call(ProductSeeder::class);
    }
}
