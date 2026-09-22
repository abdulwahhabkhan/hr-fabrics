<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (\App\Enums\Role::cases() as $role) {
            Role::factory()
                ->create([
                    'id' => $role->value,
                    'name' => $role->name,
                    'description' => str($role->name)->title(),
                ]);
        }

    }
}
