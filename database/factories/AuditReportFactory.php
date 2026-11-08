<?php

namespace Database\Factories;

use App\Models\AuditReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditReport>
 */
class AuditReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AuditReport>
     */
    protected $model = AuditReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'start_date' => today(),
            'end_date' => today(),
            'user_id' => null,
            'source' => 'console',
            'issues_count' => 0,
            'summary' => [],
            'issues' => [],
            'duration_ms' => fake()->numberBetween(50, 900),
        ];
    }

    /**
     * A report that found one issue.
     */
    public function withIssue(): self
    {
        return $this->state([
            'issues_count' => 1,
            'summary' => ['sale' => ['checked' => 1, 'issues' => 1]],
            'issues' => [[
                'module' => 'sale',
                'module_label' => 'Sale',
                'area' => 'ledger',
                'id' => 1,
                'ref_no' => '2611001',
                'party' => 'Customer',
                'date' => today()->toDateString(),
                'check' => 'Customer debit',
                'expected' => 100,
                'actual' => 0,
                'difference' => -100,
                'message' => 'Customer debit: expected 100, found 0.',
                'url' => null,
            ]],
        ]);
    }
}
