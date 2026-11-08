<?php

namespace App\Http\Resources\Settings;

use App\Models\AuditReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A stored audit report row for the history list (the issues themselves are only sent to the report page).
 *
 * @mixin AuditReport
 *
 * @property AuditReport $resource
 */
final class AuditReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'source' => $this->source,
            'issues_count' => $this->issues_count,
            'checked_count' => (int) collect($this->summary)->sum('checked'),
            'duration_ms' => $this->duration_ms,
            'run_by' => $this->user?->name,
            'created_at' => $this->created_at,
        ];
    }
}
