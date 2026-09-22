<?php

namespace App\Services\Attendance\DataTransferObjects;

use App\Enums\AttendanceMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use JsonException;

class AttendancePunchData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $externalId,
        public string $workerId,
        public string $employeeName,
        public CarbonImmutable $punchedAtDate,
        public CarbonImmutable $punchedAtTime,
        public AttendanceMethod $method,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $log
     */
    public static function fromApi(array $log): self
    {
        $worker = Arr::get($log, 'worker', []);
        $punchedAt = CarbonImmutable::parse((string) Arr::get($log, 'punchedAt'))->utc();

        return new self(
            externalId: (string) Arr::get($log, 'id', ''),
            workerId: (string) Arr::get($worker, 'id', ''),
            employeeName: self::nullableString(Arr::get($worker, 'name')) ?? (string) Arr::get($worker, 'deviceUserId',
                ''),
            punchedAtDate: $punchedAt,
            punchedAtTime: $punchedAt,
            method: AttendanceMethod::from((string) Arr::get($log, 'method', '')),
            raw: $log,
        );
    }

    /**
     * Punch date converted to the app's configured timezone — the same
     * boundary attendance_logs.punch_date and attendances.date are stored in.
     */
    public function punchDate(): string
    {
        return $this->punchedAtDate->setTimezone(config('app.timezone'))->toDateString();
    }

    /**
     * Shape handed to Eloquent's upsert(). Keys must match every chunk exactly,
     * otherwise the generated multi-row INSERT will be malformed.
     *
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function toUpsertRow(CarbonImmutable $syncedAt): array
    {
        $timezone = config('app.timezone');

        return [
            'external_id' => $this->externalId,
            'worker_name' => $this->employeeName,
            'punch_date' => $this->punchDate(),
            'punch_time' => $this->punchedAtTime->setTimezone($timezone)->toTimeString(),
            'method' => $this->method->value,
            'info' => json_encode($this->raw, JSON_THROW_ON_ERROR),
            'created_at' => $syncedAt->toDateTimeString(),
            'updated_at' => $syncedAt->toDateTimeString(),
        ];
    }

    public function isValid(): bool
    {
        return $this->externalId !== '' && $this->employeeName !== '';
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = mb_trim((string) $value);

        return $string === '' ? null : $string;
    }
}
