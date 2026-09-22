<?php

namespace Database\Factories\Attendance;

use App\Enums\AttendanceMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Random\RandomException;

class AttendanceLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Mirrors the external API's raw payload shape (see
     * tests/Fixtures/Attendance/attendance.json) so the generated
     * worker_id column resolves the same way it does for real imports.
     *
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    public function definition(): array
    {
        $externalId = bin2hex(random_bytes(12));
        $workerId = bin2hex(random_bytes(12));
        $name = fake()->firstName();
        $punchedAt = fake()->dateTimeBetween('-30 days', 'now', 'UTC');

        return [
            'external_id' => $externalId,
            'worker_name' => $name,
            'punch_date' => $punchedAt->format('Y-m-d'),
            'punch_time' => $punchedAt->format('H:i:s'),
            'method' => AttendanceMethod::FingerPrint->value,
            'info' => [
                'id' => $externalId,
                'worker' => [
                    'id' => $workerId,
                    'deviceUserId' => (string) fake()->numberBetween(1, 999),
                    'name' => $name,
                ],
                'punchedAt' => $punchedAt->format('Y-m-d\TH:i:s.v\Z'),
                'method' => AttendanceMethod::FingerPrint->value,
                'rawVerify' => AttendanceMethod::FingerPrint->rawVerify(),
                'sn' => mb_strtoupper(fake()->bothify('??#?######')),
            ],
        ];
    }

    /**
     * Attach the punch to a specific worker, keeping their identity consistent
     * across multiple punches on the same or different days.
     */
    public function forWorker(string $externalId, string $name): static
    {
        return $this->state(fn (array $attributes): array => [
            'info' => array_replace_recursive($attributes['info'], [
                'worker' => ['id' => $externalId, 'name' => $name],
            ]),
            'worker_name' => $name,
        ]);
    }

    public function face(): static
    {
        return $this->state(fn (array $attributes): array => [
            'method' => AttendanceMethod::Face->value,
            'info' => array_replace($attributes['info'], [
                'method' => AttendanceMethod::Face->value,
                'rawVerify' => AttendanceMethod::Face->rawVerify(),
            ]),
        ]);
    }
}
