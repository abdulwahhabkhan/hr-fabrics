<?php

namespace App\Console\Commands\Util;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a user, defaulting to a random password';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $roles = $this->getRoles();
        $name = text(
            label: 'Name',
            required: true,
            validate: ['name' => ['max:50']],
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: ['email' => ['email', 'max:150', Rule::unique('users')]],
        );

        $roleName = select(
            label: 'Role',
            options: $roles->values()->all(),
        );

        $password = text(
            label: 'Password',
            default: Str::password(16),
            required: true,
            validate: ['password' => [Password::min(8)->mixedCase()]],
        );

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'role_id' => $roles->search($roleName),
            'password' => Hash::make($password),
            'password_changed_at' => now(),
        ]);

        $this->info('User created successfully.');
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }

    private function getRoles(): Collection
    {
        $roles = Role::query()->orderBy('name')->pluck('name', 'id');

        if ($roles->isEmpty()) {

            $this->warn('No roles found. Super admin role created.');
            Role::query()->create(['name' => 'Super Admin', 'description' => 'Super admin role']);
            $roles = Role::query()->orderBy('name')->pluck('name', 'id');
            // return self::FAILURE;
        }

        return $roles;
    }
}
