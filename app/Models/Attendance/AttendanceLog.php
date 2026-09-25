<?php

namespace App\Models\Attendance;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property-read string $first_punch
 * @property-read string|null $last_punch
 * @property-read int $punch_count
 */
class AttendanceLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'punch_date' => 'date:Y-m-d',
        'info' => 'array',
    ];

    /**
     * Collapse punches into one row per worker per day, with the first
     * and last punch times of that day.
     *
     * @param  array<string, mixed>  $request
     */
    #[Scope]
    protected function dailyReport(Builder $query, array $request): void
    {
        $query->selectRaw('worker_name, punch_date,
                MIN(punch_time)  as first_punch,
                CASE WHEN COUNT(*) > 1 THEN MAX(punch_time) END as last_punch,
                COUNT(*)         as punch_count')
            ->groupBy('worker_name', 'punch_date')
            ->when($request['start_date'] ?? null,
                fn (Builder $q, string $v): Builder => $q->where('punch_date', '>=', $v))
            ->when($request['end_date'] ?? null,
                fn (Builder $q, string $v): Builder => $q->where('punch_date', '<=', $v))
            ->when($request['worker_name'] ?? null,
                fn (Builder $q, string $v): Builder => $q->where('worker_name', 'like', "%{$v}%"))
            ->orderByDesc('punch_date')
            ->orderBy('worker_name');
    }
}
