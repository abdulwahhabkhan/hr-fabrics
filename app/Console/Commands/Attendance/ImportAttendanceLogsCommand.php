<?php

namespace App\Console\Commands\Attendance;

use App\Models\Attendance\AttendanceLog;
use App\Services\Attendance\AttendanceImporter;
use App\Services\Attendance\AttendanceProjector;
use App\Services\Attendance\DataTransferObjects\AttendancePunchData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ImportAttendanceLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:import {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import attendance punch logs from the external API (defaults --from to 5 hours ago), optionally bounded by --from/--to';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceImporter $importer, AttendanceProjector $projector): int
    {
        $from = $this->option('from')
            ? CarbonImmutable::parse($this->option('from'), 'UTC')
            : CarbonImmutable::now('UTC')->subHours(5);
        $to = $this->option('to') ? CarbonImmutable::parse($this->option('to'), 'UTC') : null;

        $punches = collect($importer->getLogs($from, $to))
            ->filter(fn (AttendancePunchData $punch): bool => $punch->isValid())
            ->values();

        if ($punches->isEmpty()) {
            $this->info('No punch logs found for the given window.');

            return self::SUCCESS;
        }

        $syncedAt = CarbonImmutable::now('UTC');

        $rows = $punches
            ->map(fn (AttendancePunchData $punch): array => $punch->toUpsertRow($syncedAt))
            ->all();

        AttendanceLog::query()->upsert(
            $rows,
            uniqueBy: ['external_id'],
            update: ['worker_name', 'punch_date', 'punch_time', 'method', 'info', 'updated_at'],
        );

        $this->info(sprintf('Imported %d punch log(s).', count($rows)));

        $withWorkerId = $punches->filter(fn (AttendancePunchData $punch): bool => $punch->workerId !== '');

        if ($withWorkerId->count() < $punches->count()) {
            $this->warn(sprintf('%d punch log(s) had no worker id and were not projected.',
                $punches->count() - $withWorkerId->count()));
        }

        $projected = $projector->project(
            $withWorkerId->pluck('workerId')->all(),
            $withWorkerId->map(fn (AttendancePunchData $punch): string => $punch->punchDate())->all(),
        );

        $this->info(sprintf('Projected %d attendance day(s).', $projected));

        return self::SUCCESS;
    }
}
