<?php

namespace App\Console\Commands\Util;

use App\Services\AuditReportService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class AuditReportCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'audit:run
        {--start= : Start date (Y-m-d). Defaults to today}
        {--end= : End date (Y-m-d). Defaults to the start date}';

    /**
     * @var string
     */
    protected $description = 'Audit that sales, sale returns, receivings and purchases match their inventory and ledger postings, and store the report';

    public function handle(AuditReportService $service): int
    {
        try {
            $start = $this->option('start') ? CarbonImmutable::createFromFormat('!Y-m-d', $this->option('start')) : CarbonImmutable::today();
            $end = $this->option('end') ? CarbonImmutable::createFromFormat('!Y-m-d', $this->option('end')) : $start;
        } catch (Throwable) {
            $this->error('Dates must be in Y-m-d format, e.g. --start=2026-11-01 --end=2026-11-04');

            return self::INVALID;
        }

        if ($end->lt($start)) {
            $this->error('The end date must be on or after the start date.');

            return self::INVALID;
        }

        $this->info("Auditing {$start->toDateString()} to {$end->toDateString()}…");
        $report = $service->run($start, $end);

        $this->table(
            ['Module', 'Checked', 'Issues'],
            collect(AuditReportService::MODULES)->map(fn (string $label, string $key): array => [
                $label,
                $report->summary[$key]['checked'] ?? 0,
                $report->summary[$key]['issues'] ?? 0,
            ])->values(),
        );

        if (! $report->hasIssues()) {
            $this->info("No issues found. Report #{$report->id}.");

            return self::SUCCESS;
        }

        $this->table(
            ['Module', 'Ref', 'Date', 'Area', 'Detail'],
            collect($report->issues)->map(fn (array $issue): array => [
                $issue['module_label'],
                $issue['ref_no'] ?? '#'.$issue['id'],
                $issue['date'],
                $issue['area'],
                $issue['message'],
            ]),
        );
        $this->warn("{$report->issues_count} issue(s) found. Report #{$report->id}.");

        return self::FAILURE;
    }
}
