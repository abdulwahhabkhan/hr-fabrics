<?php

use App\Console\Commands\Util\AuditReportCommand;
use App\Models\AuditReport;
use App\Models\Sales\Order;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;

test('a clean day is stored and succeeds', function () {
    artisan(AuditReportCommand::class)
        ->expectsOutputToContain('No issues found')
        ->assertSuccessful();

    $report = AuditReport::query()->sole();
    expect($report->source)->toBe('console')
        ->and($report->user_id)->toBeNull()
        ->and($report->start_date->toDateString())->toBe(today()->toDateString());
});

test('a day with issues is stored and fails, listing them', function () {
    Order::factory()->closed()->create(['allocation' => 2, 'invoice_no' => '261100001', 'created_at' => now()]);

    artisan(AuditReportCommand::class, ['--start' => today()->toDateString(), '--end' => today()->toDateString()])
        ->expectsOutputToContain('issue(s) found')
        ->assertFailed();

    expect(AuditReport::query()->sole()->issues_count)->toBeGreaterThan(0);
});

test('invalid dates are refused without storing a report', function (array $options) {
    artisan(AuditReportCommand::class, $options)->assertExitCode(2);

    assertDatabaseCount(AuditReport::class, 0);
})->with([
    'not a date' => [['--start' => 'yesterday']],
    'end before start' => [['--start' => '2026-11-08', '--end' => '2026-11-01']],
]);
