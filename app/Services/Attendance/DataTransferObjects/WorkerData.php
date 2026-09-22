<?php

namespace App\Services\Attendance\DataTransferObjects;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use JsonException;

class WorkerData
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $worker
     */
    public static function fromApi(array $worker): self
    {
        return new self(
            id: (string) Arr::get($worker, 'id', ''),
            name: (string) Arr::get($worker, 'name', ''),
            raw: $worker,
        );
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'info' => json_encode($this->raw, JSON_THROW_ON_ERROR),
            'created_at' => $syncedAt->toDateTimeString(),
            'updated_at' => $syncedAt->toDateTimeString(),
        ];
    }

    public function isValid(): bool
    {
        return $this->id !== '' && $this->name !== '';
    }
}
