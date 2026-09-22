<?php

namespace Database\Factories\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'worker_id' => fn () => Employee::factory(),
            'date' => now(),
            'in' => now(),
            'out' => now(),
            'status' => fake()->randomElement(AttendanceStatus::cases()),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Absent,
        ]);
    }

    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Present,
        ]);
    }

    public function halfDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::HalfDay,
        ]);
    }

    public function leave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AttendanceStatus::Leave,
        ]);
    }
}
