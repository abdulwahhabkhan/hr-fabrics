<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(1)->create([
            'email' => 'abdulwahhabkhan@hotmail.com',
            'name' => 'Abdul Khan',
            'role_id' => 1,
            'password' => Hash::make('Hmag@2022'),
        ]);
        User::factory(1)->create([
            'email' => 'kashif@hmag.com',
            'name' => 'Kashif Sharif',
            'role_id' => 1,
            'password' => Hash::make('Hw342u3492u4'),
        ]);
    }
}
