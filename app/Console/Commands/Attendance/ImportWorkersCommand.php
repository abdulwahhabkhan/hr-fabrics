<?php

namespace App\Console\Commands\Attendance;

use App\Models\Attendance\Employee;
use App\Services\Attendance\AttendanceImporter;
use App\Services\Attendance\DataTransferObjects\WorkerData;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class ImportWorkersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:import-workers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import worker/employee records from the external API';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceImporter $importer): int
    {
        $workers = $importer->getWorkers();

        if ($workers === []) {
            $this->info('No workers found.');

            return self::SUCCESS;
        }

        $syncedAt = CarbonImmutable::now('UTC');

        $rows = collect($workers)
            ->filter(fn (WorkerData $worker): bool => $worker->isValid())
            ->map(fn (WorkerData $worker): array => $worker->toUpsertRow($syncedAt))
            ->values()
            ->all();

        Employee::query()->upsert(
            $rows,
            uniqueBy: ['id'],
            update: ['name', 'info', 'updated_at'],
        );

        $this->info(sprintf('Imported %d worker(s).', count($rows)));

        return self::SUCCESS;
    }
}
