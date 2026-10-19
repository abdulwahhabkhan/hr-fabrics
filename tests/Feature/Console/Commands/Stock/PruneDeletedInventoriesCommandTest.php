<?php

use App\Console\Commands\Stock\PruneDeletedInventoriesCommand;
use App\Models\Stock\Inventory;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
    $this->freezeTime();
});

test('removes inventory soft-deleted more than a year ago', function () {
    // arrange
    $inventory = Inventory::factory()->withPurchase()->create(['deleted_at' => now()->subYear()->subDay()]);

    // action
    artisan(PruneDeletedInventoriesCommand::class)->assertSuccessful();

    // assert
    assertModelMissing($inventory);
});

test('keeps inventory soft-deleted less than a year ago', function () {
    // arrange
    $inventory = Inventory::factory()->withPurchase()->create(['deleted_at' => now()->subYear()->addDay()]);

    // action
    artisan(PruneDeletedInventoriesCommand::class)->assertSuccessful();

    // assert
    assertSoftDeleted($inventory);
});

test('keeps inventory that is not deleted', function () {
    // arrange
    $inventory = Inventory::factory()->withPurchase()->create();

    // action
    artisan(PruneDeletedInventoriesCommand::class)->assertSuccessful();

    // assert
    assertModelExists($inventory);
});
