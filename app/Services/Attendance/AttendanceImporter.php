<?php

namespace App\Services\Attendance;

use App\Services\Attendance\DataTransferObjects\AttendancePunchData;
use App\Services\Attendance\DataTransferObjects\WorkerData;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AttendanceImporter
{
    private const int PAGE_SIZE = 500;

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
    ) {}

    /**
     * Fetch every punch log in the given window, walking cursor pagination.
     * Omitting from/to leaves the date bound off the request entirely,
     * rather than defaulting to any particular day.
     *
     * @return array<int, AttendancePunchData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getLogs(?CarbonImmutable $from = null, ?CarbonImmutable $to = null): array
    {
        $punches = [];
        $cursor = null;

        do {
            $response = Http::withToken($this->apiKey)
                ->get("{$this->baseUrl}/logs", array_filter([
                    'from' => $from ? self::formatIso($from) : null,
                    'to' => $to ? self::formatIso($to) : null,
                    'limit' => self::PAGE_SIZE,
                    'cursor' => $cursor,
                ]))
                ->throw();

            $body = $response->json();

            foreach ($body['logs'] ?? [] as $log) {
                $punches[] = AttendancePunchData::fromApi($log);
            }

            $cursor = $body['nextCursor'] ?? null;
        } while ($cursor !== null);

        return $punches;
    }

    /**
     * Fetch every worker/employee record from the external API.
     *
     * @return array<int, WorkerData>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getWorkers(): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/workers")
            ->throw();

        return collect($response->json())
            ->map(fn (array $worker): WorkerData => WorkerData::fromApi($worker))
            ->values()
            ->all();
    }

    /**
     * Match the API's "2026-07-18T13:19:36.000Z" format exactly.
     */
    private static function formatIso(CarbonImmutable $date): string
    {
        return $date->utc()->format('Y-m-d\TH:i:s.v\Z');
    }
}
