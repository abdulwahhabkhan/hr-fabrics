<?php

namespace Database\Factories\Attendance;

use Illuminate\Database\Eloquent\Factories\Factory;
use Random\RandomException;

class EmployeeFactory extends Factory
{
    /**
     * @throws RandomException
     */
    public function definition(): array
    {
        return [
            'id' => bin2hex(random_bytes(12)),
            'name' => fake()->firstName(),
            'info' => [
                'deviceUserId' => (string) fake()->numberBetween(1, 999),
                'nameSource' => 'device',
                'lastMethod' => 'fingerprint',
            ],
        ];
    }
}
