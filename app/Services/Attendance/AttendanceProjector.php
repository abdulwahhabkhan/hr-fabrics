<?php

namespace App\Services\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\Attendance\Attendance;
use App\Models\Attendance\AttendanceLog;
use Carbon\CarbonImmutable;

class AttendanceProjector
{
    private const int UPSERT_CHUNK_SIZE = 500;

    /**
     * Rebuild one attendances row per worker per day from attendance_logs,
     * for the given workers and dates. Recomputed from every log on record
     * for that (worker, day) pair rather than merged from a single import
     * batch, so re-imports, overlapping windows, and out-of-order punches
     * self-correct instead of drifting.
     *
     * The first punch of the day becomes `in`, the last becomes `out` (null
     * when there is only one punch). `status` is only ever supplied on
     * insert — a human-assigned status (Leave, HalfDay, ...) is never
     * overwritten by a later import.
     *
     * @param  array<int, string>  $workerIds
     * @param  array<int, string>  $dates  Y-m-d strings, app timezone.
     * @return int Number of (worker, day) rows written.
     */
    public function project(array $workerIds, array $dates): int
    {
        $workerIds = array_values(array_unique(array_filter($workerIds, fn (string $id): bool => $id !== '')));
        $dates = array_values(array_unique($dates));

        if ($workerIds === [] || $dates === []) {
            return 0;
        }

        $now = CarbonImmutable::now('UTC')->toDateTimeString();

        $rows = AttendanceLog::query()
            ->toBase()
            ->select(['worker_id', 'punch_date'])
            ->selectRaw('MIN(punch_time) as first_punch')
            ->selectRaw('CASE WHEN COUNT(*) > 1 THEN MAX(punch_time) END as last_punch')
            ->whereNotNull('worker_id')
            ->whereIn('worker_id', $workerIds)
            ->whereIn('punch_date', $dates)
            ->groupBy('worker_id', 'punch_date')
            ->get()
            ->map(fn (object $row): array => [
                'worker_id' => $row->worker_id,
                'date' => $row->punch_date,
                'in' => $row->first_punch,
                'out' => $row->last_punch,
                'status' => AttendanceStatus::Present->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->sortBy(fn (array $row): string => $row['worker_id'].$row['date'])
            ->values();

        $rows->chunk(self::UPSERT_CHUNK_SIZE)->each(
            fn ($chunk) => Attendance::query()->upsert(
                $chunk->all(),
                uniqueBy: ['worker_id', 'date'],
                update: ['in', 'out', 'updated_at'],
            )
        );

        return $rows->count();
    }
}
