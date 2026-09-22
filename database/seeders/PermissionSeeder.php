<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Artisan::call('permissions:generate');
        $role = Role::find(\App\Enums\Role::SuperAdmin->value);
        if (! $role) {
            $this->command->warn('Role not found');

            return;
        }
        $permissions = Permission::all();
        $role->permissions()->sync($permissions);

    }
}
