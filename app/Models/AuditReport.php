<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Carbon\CarbonInterface;
use Database\Factories\AuditReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One stored run of the sales / purchases audit (see AuditReportService).
 *
 * @property CarbonInterface $start_date
 * @property CarbonInterface $end_date
 * @property array<string, array{checked: int, issues: int}> $summary
 * @property array<int, array<string, mixed>> $issues
 */
class AuditReport extends Model
{
    use BelongsToUser;

    /** @use HasFactory<AuditReportFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $guarded = ['id'];

    public function hasIssues(): bool
    {
        return $this->issues_count > 0;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'summary' => 'array',
            'issues' => 'array',
            'issues_count' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
