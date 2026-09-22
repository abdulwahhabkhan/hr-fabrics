<?php

use App\Enums\StatusText;
use App\Facades\Permission;
use App\Models\Accounts\Account;
use App\Models\Action\Log;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Stock\Inventory;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function (): void {
    $this->fakeHavePermission();
});
test('fabric receiving list', function () {
    // Arrange
    $user = $this->getAdmin();
    FabricReceiving::factory()
        ->count(2)
        ->has(
            FabricReceivingItem::factory()->box()->count(2),
            'items'
        )->create();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.index'));
    // Assert
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingIndex')
        ->has('rows.data', 2)
    );
});

test('fabric create receiving', function () {
    // Arrange
    $user = $this->getAdmin();
    $supplier = Account::factory()->supplier()->create();
    $supplier['supplier_id'] = $supplier->id;
    $body = [
        'supplier' => $supplier,
    ];

    // Act
    $response = $this->actingAs($user)->post(route('purchases.fabric-receivings.store'), $body);

    // Assert
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    assertDatabaseHas(FabricReceiving::class, ['supplier_id' => $supplier->id]);

});

test('fabric receiving update', function () {

    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->create();
    $boxes = FabricReceivingItem::factory()->box()->count(3)->create(['fabric_receiving_id' => $stock->id]);
    $thaan = FabricReceivingItem::factory()->thaan()->count(3)->create(['fabric_receiving_id' => $stock->id]);
    $info = ['remarks' => 'Testings'];
    $body = [
        'bilti_no' => 'BL-1000',
        'lot_no' => 'LOT-1000',
        'status' => 'Open',
    ];
    $total_meters = $boxes->sum('total_qty') + $thaan->sum('total_qty');
    $total_qty = $boxes->sum('qty') + $thaan->sum('qty');
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.fabric-receivings.update', $stock->id), array_merge($body, ['info' => $info]));

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas(FabricReceiving::class, $body);
    $this->assertDatabaseHas(
        FabricReceiving::class,
        ['info' => json_encode($info), 'total_qty' => $total_qty, 'total_meters' => $total_meters]
    );
});

test('fabric received confirm inventory updated', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->create();
    FabricReceivingItem::factory()->suit()->create(['fabric_receiving_id' => $stock->id]);
    FabricReceivingItem::factory()->thaan()->create(['fabric_receiving_id' => $stock->id]);
    FabricReceivingItem::factory()->box()->count(3)->create(['fabric_receiving_id' => $stock->id]);
    $info = ['remarks' => 'Testings'];
    $transactionDate = today();
    $body = [
        'bilti_no' => 'BL-1000',
        'lot_no' => 'LOT-1000',
        'status' => StatusText::Close->value,
    ];
    $items = $stock->items;
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.fabric-receivings.update', $stock->id),
            array_merge($body, ['info' => $info]));

    // Assert
    $body['transaction_date'] = $transactionDate;
    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas(FabricReceiving::class, $body);
    $this->assertDatabaseHas(FabricReceiving::class, ['info' => json_encode($info)]);
    $this->assertDatabaseHas(Log::class, [
        'loggable_type' => $stock->getMorphClass(),
        'loggable_id' => $stock->id,
        'log' => json_encode([
            'action' => 'Stock receiving confirmed',
            'user' => ['id' => $user->id, 'name' => $user->name],
        ]),
    ]);
    assertDatabaseCount(Inventory::class, $items->count());
    foreach ($items as $item) {
        /** @var Inventory $inventory */
        $inventory = $item->inventory->first();
        expect($inventory)
            ->product_id->toBe($item->product_id)
            ->outbound_id->toBeNull()
            ->outbound_item_id->toBeNull()
            ->outbound_type->toBeNull()
            ->outbound_on->toBeNull()
            ->qty->toBe($item->qty)
            ->size->toBe($item->size)
            ->meters->toBe($item->total_qty)
            ->transaction_date->toDateString()->toBe($transactionDate->toDateString());
    }

});

test('fabric receiving update requires bilti and lot number when closing', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()->create();
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.fabric-receivings.update', $stock->id), [
            'status' => StatusText::Close->value,
        ]);
    // Assert
    $response->assertSessionHasErrors(['bilti_no', 'lot_no']);
});

test('fabric receiving list filters by request fields', function () {
    // Arrange
    $user = $this->getAdmin();
    $supplier = Account::factory()->supplier()->create(['name' => 'Acme Textiles']);
    $match = FabricReceiving::factory()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PO-MATCH-001',
        'bilti_no' => 'BILTI-MATCH',
        'lot_no' => 'LOT-MATCH',
        'invoiced' => true,
    ]);
    FabricReceiving::factory()->create([
        'invoice_no' => 'PO-OTHER-001',
        'bilti_no' => 'BILTI-OTHER',
        'lot_no' => 'LOT-OTHER',
        'invoiced' => false,
    ]);
    $filters = [
        'bilti_no' => 'MATCH',
        'lot_no' => 'MATCH',
        'supplier_name' => 'Acme',
        'ref_no' => 'MATCH',
        'invoiced' => '1',
    ];
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.index', $filters));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingIndex')
        ->has('rows.data', 1)
        ->where('rows.data.0.id', $match->id)
        ->where('filters', $filters)
    );
    expect(session('purchase.fabric_receivings'))->toBe($filters);
});

test('fabric receiving list remember forget clears saved filters', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->withSession(['purchase.fabric_receivings' => ['bilti_no' => 'OLD']]);
    // Act
    $response = $this->actingAs($user)
        ->get(route('purchases.fabric-receivings.index', ['remember' => 'forget']));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingIndex')
        ->where('filters', [])
    );
    expect(session()->has('purchase.fabric_receivings'))->toBeFalse();
});

test('fabric receiving list exposes permission flags as false by default', function () {
    // Arrange
    Permission::fake(['purchases.fabric-receivings.index' => true]);
    $user = $this->userWithoutPermissions();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.index'));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingIndex')
        ->where('canAdd', false)
    );
});

test('fabric receiving list exposes canAdd true when user has the store permission', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.store');
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.index'));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingIndex')
        ->where('canAdd', true)
    );
});

test('fabric receiving create form', function () {
    // Arrange
    $user = $this->getAdmin();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.create'));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingFormNew')
        ->has('suppliers')
    );
});

test('fabric receiving edit form', function () {
    // Arrange
    $user = $this->getAdmin();
    $this->attachPermissions($user, 'purchases.fabric-receivings.edit');
    $stock = FabricReceiving::factory()
        ->has(FabricReceivingItem::factory()->box()->count(2), 'items')
        ->create();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.edit', $stock->id));
    // Assert
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingForm')
        ->has('stock')
        ->has('files')
        ->has('status_list')
        ->has('products')
        ->has('items.data', 2)
    );
});

test('fabric receiving edit forbidden when stock is not open', function () {
    // Arrange
    Permission::fake(['purchases.fabric-receivings.edit' => true]);
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->confirmed()->create();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.edit', $stock->id));
    // Assert
    $response->assertForbidden();
});

test('fabric receiving update forbidden when stock is not open', function () {
    // Arrange
    Permission::fake(['purchases.fabric-receivings.update' => true]);
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->confirmed()->create();
    // Act
    $response = $this->actingAs($user)
        ->put(route('purchases.fabric-receivings.update', $stock->id), ['bilti_no' => 'BL-2000']);
    // Assert
    $response->assertForbidden();
    $this->assertDatabaseMissing(FabricReceiving::class, ['bilti_no' => 'BL-2000']);
});

test('fabric receiving delete', function () {
    // Arrange
    $user = $this->userWithoutPermissions();
    $this->attachPermissions($user, 'purchases.fabric-receivings.destroy');
    $stock = FabricReceiving::factory()->create();
    $items = FabricReceivingItem::factory()->box()->count(2)->create(['fabric_receiving_id' => $stock->id]);
    // Act
    $response = $this->actingAs($user)->delete(route('purchases.fabric-receivings.destroy', $stock->id));
    // Assert
    $response->assertRedirect(route('purchases.fabric-receivings.index'));
    $this->assertSoftDeleted(FabricReceiving::class, ['id' => $stock->id, 'status' => 'Cancel']);
    foreach ($items as $item) {
        assertDatabaseHas(FabricReceivingItem::class, ['id' => $item->id, 'status' => 2]);
    }
    $this->assertDatabaseHas(Log::class, [
        'loggable_type' => $stock->getMorphClass(),
        'loggable_id' => $stock->id,
        'log' => json_encode([
            'action' => 'Stock receiving deleted',
            'user' => ['id' => $user->id, 'name' => $user->name],
        ]),
    ]);
});

test('fabric receiving delete forbidden when stock is not open', function () {
    // Arrange
    Permission::fake(['purchases.fabric-receivings.destroy' => true]);
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()->confirmed()->create();
    // Act
    $response = $this->actingAs($user)->delete(route('purchases.fabric-receivings.destroy', $stock->id));
    // Assert
    $response->assertForbidden();
    $this->assertDatabaseHas(FabricReceiving::class, ['id' => $stock->id, 'deleted_at' => null]);
});

test('fabric receiving preview', function () {
    // Arrange
    $user = $this->getAdmin();
    $stock = FabricReceiving::factory()
        ->has(
            FabricReceivingItem::factory()->box()->count(2),
            'items'
        )->create();
    // Act
    $response = $this->actingAs($user)->get(route('purchases.fabric-receivings.show', $stock->id));

    // Assert
    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/FabricReceivings/FabricReceivingView')
        ->has('order')
        ->has('total_summary')
    );
});
