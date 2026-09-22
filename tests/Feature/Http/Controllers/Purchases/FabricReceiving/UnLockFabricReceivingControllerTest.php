<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Enums\StatusText;
use App\Models\Action\Log;
use App\Models\Purchase\FabricReceiving;

use function Pest\Laravel\assertDatabaseCount;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->attachPermissions($this->user, 'purchases.fabric-receivings.unlock');
});
test('exceptions test', function (StatusText $status, bool $updatedAt) {
    // Arrange
    $user = $this->user;

    $stock = FabricReceiving::factory()
        ->hasItems(2)
        ->create([
            'status' => $status,
        ]);
    $body = [];
    if ($updatedAt) {
        $this->travel(2)->days();
    }
    // Act
    $response = $this->actingAs($user)
        ->post(route('purchases.fabric-receivings.unlock', $stock->id), $body);

    // Assert
    $response->assertForbidden();

})->with([
    'open' => [StatusText::Open, false],
    'existing-old' => [StatusText::Close, true],
]);

test('unlock confirm po receiving', function () {
    // Arrange
    Queue::fake();
    $user = $this->user;
    $stocks = FabricReceiving::factory(2)->confirmed()
        ->hasItems(2)
        ->create(['transaction_date' => now()]);
    $body = [];
    $stock = $stocks->first();
    $stock_2 = $stocks[1];
    resolve(FabricReceivingConfirmed::class)->handle($stock);
    resolve(FabricReceivingConfirmed::class)->handle($stock_2);

    // Act
    $response = $this->actingAs($user)
        ->post(route('purchases.fabric-receivings.unlock', $stock->id), $body);

    // Assert
    $response->assertRedirect(route('purchases.fabric-receivings.edit', $stock->id));
    Queue::assertNothingPushed();
    expect($stock->inventories()->count())->toBe(0)
        ->and($stock_2->inventories()->count())->toBeGreaterThan(0);
    assertDatabaseCount(Log::class, 1);
});
