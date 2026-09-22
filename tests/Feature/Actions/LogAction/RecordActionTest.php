<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\LogAction\RecordAction;
use App\Models\Purchase\FabricReceiving;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

test('records log with action string', function () {
    // Arrange
    $stock = FabricReceiving::factory()->create();
    $user = User::factory()->create();

    // Action
    $log = resolve(RecordAction::class)->handle($stock, $user, 'created');

    // Assert
    expect($log->log['action'])->toBe('created')
        ->and($log->log['user'])->toBe(['id' => $user->id, 'name' => $user->name])
        ->and($log->loggable_id)->toBe($stock->id)
        ->and($log->loggable_type)->toBe($stock->getMorphClass());
    assertDatabaseHas($log->getTable(), ['id' => $log->id]);
});

test('records log with log array', function () {
    // Arrange
    $stock = FabricReceiving::factory()->create();
    $user = User::factory()->create();

    // Action
    $log = resolve(RecordAction::class)->handle($stock, $user, log: ['field' => 'total_qty', 'from' => 0, 'to' => 5]);

    // Assert
    expect($log->log['field'])->toBe('total_qty')
        ->and($log->log['from'])->toBe(0)
        ->and($log->log['to'])->toBe(5)
        ->and($log->log)->not->toHaveKey('action')
        ->and($log->log['user'])->toBe(['id' => $user->id, 'name' => $user->name]);
});

test('merges action into given log array', function () {
    // Arrange
    $stock = FabricReceiving::factory()->create();
    $user = User::factory()->create();

    // Action
    $log = resolve(RecordAction::class)->handle($stock, $user, 'updated', ['field' => 'status']);

    // Assert
    expect($log->log['action'])->toBe('updated')
        ->and($log->log['field'])->toBe('status')
        ->and($log->log['user'])->toBe(['id' => $user->id, 'name' => $user->name]);
});

test('throws when neither action nor log given', function () {
    // Arrange
    $stock = FabricReceiving::factory()->create();
    $user = User::factory()->create();

    // Action & Assert
    expect(fn () => resolve(RecordAction::class)->handle($stock, $user))
        ->toThrow(InvalidArgumentException::class, 'Action or Log is required');
});

test('persists log against the model logs relation', function () {
    // Arrange
    $stock = FabricReceiving::factory()->create();
    $user = User::factory()->create();

    // Action
    $log = resolve(RecordAction::class)->handle($stock, $user, 'created');

    // Assert
    expect($stock->logs()->whereKey($log->id)->exists())->toBeTrue();
});
