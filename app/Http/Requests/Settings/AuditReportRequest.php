<?php

namespace App\Http\Requests\Settings;

use App\Models\Purchase\FabricReceiving;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class AuditReportRequest extends FormRequest
{
    /** Longest range a report may be run for from the page, to keep the request fast. */
    public const MAX_DAYS = 366;

    /**
     * Only master users run audits.
     */
    public function authorize(): bool
    {
        return Gate::allows('masterTools', FabricReceiving::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                    return;
                }
                if ($this->startDate()->diffInDays($this->endDate()) >= self::MAX_DAYS) {
                    $validator->errors()->add('end_date', 'The range may be at most one year. Use `php artisan audit:run` for longer ranges.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Choose a start date.',
            'start_date.date_format' => 'The start date must be in YYYY-MM-DD format.',
            'end_date.required' => 'Choose an end date.',
            'end_date.date_format' => 'The end date must be in YYYY-MM-DD format.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    public function startDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('start_date')->toString());
    }

    public function endDate(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->string('end_date')->toString());
    }
}
