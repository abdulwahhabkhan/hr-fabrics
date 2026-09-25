<?php

use App\Enums\AttendanceMethod;
use App\Services\Attendance\AttendanceImporter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.attendance.base_url' => 'https://api.shehryar.me/api/public/attendance',
        'services.attendance.api_key' => 'att_test_key',
    ]);
});

test('fetches logs without a from/to bound when none is given', function () {
    // Arrange
    $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/Attendance/attendance.json')), true);

    Http::fake([
        '*/logs*' => Http::response($fixture),
    ]);

    // Action
    $punches = app(AttendanceImporter::class)->getLogs();

    // Assert
    expect($punches)->toHaveCount(2)
        ->and($punches[0]->externalId)->toBe('6a5bd63a5eb04baf64c3ea1b')
        ->and($punches[0]->employeeName)->toBe('Shehryar')
        ->and($punches[0]->method)->toBe(AttendanceMethod::FingerPrint)
        ->and($punches[0]->punchedAtDate->format('Y-m-d\TH:i:s.v\Z'))->toBe('2026-07-18T19:38:32.000Z')
        ->and($punches[0]->raw)->toBe($fixture['logs'][0]);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://api.shehryar.me/api/public/attendance/logs')
        && ! array_key_exists('from', $request->data())
        && ! array_key_exists('to', $request->data())
        && $request['limit'] === 500
        && $request->hasHeader('Authorization', 'Bearer att_test_key'));
});

test('fetches logs for an explicit window', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response(['logs' => []]),
    ]);

    $from = CarbonImmutable::parse('2026-07-01T00:00:00Z');
    $to = CarbonImmutable::parse('2026-07-02T00:00:00Z');

    // Action
    $punches = app(AttendanceImporter::class)->getLogs($from, $to);

    // Assert
    expect($punches)->toBeEmpty();

    Http::assertSent(fn (Request $request): bool => $request['from'] === $from->format('Y-m-d\TH:i:s.v\Z')
        && $request['to'] === $to->format('Y-m-d\TH:i:s.v\Z'));
});

test('walks cursor pagination until nextCursor is null', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::sequence()
            ->push([
                'logs' => [[
                    'id' => 'page-1-log',
                    'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
                    'punchedAt' => '2026-07-18T08:00:00.000Z',
                    'method' => 'card',
                    'rawVerify' => '4',
                    'sn' => 'TTQ5251000193',
                ]],
                'nextCursor' => 'cursor-abc',
            ])
            ->push([
                'logs' => [[
                    'id' => 'page-2-log',
                    'worker' => ['id' => 'w1', 'deviceUserId' => '1', 'name' => 'Ali'],
                    'punchedAt' => '2026-07-18T09:00:00.000Z',
                    'method' => 'card',
                    'rawVerify' => '4',
                    'sn' => 'TTQ5251000193',
                ]],
            ]),
    ]);

    // Action
    $punches = app(AttendanceImporter::class)->getLogs();

    // Assert
    expect($punches)->toHaveCount(2)
        ->and($punches[0]->externalId)->toBe('page-1-log')
        ->and($punches[1]->externalId)->toBe('page-2-log');

    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => ($request['cursor'] ?? null) === 'cursor-abc');
});

test('falls back to the device user id when the worker has no name', function () {
    // Arrange
    Http::fake([
        '*/logs*' => Http::response([
            'logs' => [[
                'id' => 'unnamed-log',
                'worker' => ['id' => 'w9', 'deviceUserId' => '9'],
                'punchedAt' => '2026-07-18T08:00:00.000Z',
                'method' => 'password',
                'rawVerify' => '3',
                'sn' => 'TTQ5251000193',
            ]],
        ]),
    ]);

    // Action
    $punches = app(AttendanceImporter::class)->getLogs();

    // Assert
    expect($punches)->toHaveCount(1)
        ->and($punches[0]->employeeName)->toBe('9');
});
