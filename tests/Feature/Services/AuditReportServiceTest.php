<?php

/** @noinspection PhpUnhandledExceptionInspection */

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Actions\Inbound\Purchase\UpdatePurchaseTotal;
use App\Actions\Outbound\SaleOrders\ConfirmOrder;
use App\Actions\Outbound\SaleOrders\UpdateOrderTotal;
use App\Actions\Outbound\SaleReturns\ConfirmSaleReturn;
use App\Enums\PaymentMode;
use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\AuditReport;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\FabricReceivingItem;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Sales\Order;
use App\Models\Sales\OrderItem;
use App\Models\Sales\SalesReturn;
use App\Models\Sales\SalesReturnItem;
use App\Models\Stock\Inventory;
use App\Services\AuditReportService;

beforeEach(function () {
    $this->user = $this->getAdmin();
    $this->cash = $this->creatCashAccount();
});

/**
 * Run the audit for the current day and return the stored report.
 */
function runAudit(): AuditReport
{
    return resolve(AuditReportService::class)->run(today(), today());
}

/**
 * Names of the checks that failed in a report, optionally for one module.
 *
 * @return array<int, string>
 */
function failedChecks(AuditReport $report, ?string $module = null): array
{
    return collect($report->issues)
        ->when($module, fn ($issues) => $issues->where('module', $module))
        ->pluck('check')
        ->all();
}

/**
 * A sale order confirmed through the real action, with stock to book.
 */
function confirmedSale(array $attributes = [], bool $withCommission = false): Order
{
    $order = Order::factory()->closed()
        ->has($withCommission ? OrderItem::factory()->commission(2) : OrderItem::factory()->thaan(), 'items')
        ->create($attributes);
    $item = $order->items->first();
    $stock = FabricReceiving::factory()->confirmed()
        ->has(FabricReceivingItem::factory()->state([
            'product_id' => $item->product_id,
            'unit' => $item->unit->value,
            'qty' => $item->qty,
            'size' => $item->size,
            'total_qty' => $item->total_qty,
        ]), 'items')
        ->create();
    resolve(FabricReceivingConfirmed::class)->handle($stock);
    resolve(UpdateOrderTotal::class)->handle($order);
    resolve(ConfirmOrder::class)->handle($order->refresh(), test()->user);

    return $order->refresh();
}

/**
 * A sale return confirmed through the real action.
 */
function confirmedSaleReturn(string $paymentMode = 'Credit'): SalesReturn
{
    $customer = Account::factory()->customer()->create();
    $return = SalesReturn::factory()->closed()
        ->has(SalesReturnItem::factory()->state(['qty' => 2, 'size' => 10, 'total_qty' => 20, 'total_amount' => 1000]), 'returnItems')
        ->create(['customer_id' => $customer->id, 'payment_mode' => $paymentMode, 'total_amount' => 1000, 'total_qty' => 20]);
    resolve(ConfirmSaleReturn::class)->handle($return, test()->user);

    return $return->refresh();
}

/**
 * A fabric receiving confirmed through the real action.
 */
function confirmedReceiving(): FabricReceiving
{
    $stock = FabricReceiving::factory()->hasItems(1)->confirmed()->create(['invoiced' => false]);
    resolve(FabricReceivingConfirmed::class)->handle($stock);

    return $stock->refresh();
}

/**
 * A fabric purchase confirmed through the real action for the given receiving.
 */
function confirmedPurchase(FabricReceiving $stock, int $price = 4250): Purchase
{
    $item = $stock->items->first();
    $purchase = Purchase::factory()->confirmed()->create([
        'stock_id' => $stock->id,
        'stock_ids' => (string) $stock->id,
        'supplier_id' => $stock->supplier_id,
        'total' => 5000,
        'transaction_date' => today(),
    ]);
    PurchaseItem::factory()->create([
        'purchase_id' => $purchase->id,
        'product_id' => $item->product_id,
        'unit' => $item->unit,
        'size' => $item->size,
        'qty' => $item->qty,
        'total_qty' => $item->total_qty,
        'price' => $price,
        'total' => 5000,
    ]);
    resolve(UpdatePurchaseTotal::class)->handle($purchase);
    resolve(ConfirmPurchaseActions::class)->handle($purchase, test()->user);

    return $purchase->refresh();
}

test('an empty period produces a clean stored report', function () {
    $report = runAudit();

    expect($report->exists)->toBeTrue()
        ->and($report->issues_count)->toBe(0)
        ->and($report->summary)->toHaveKeys(array_keys(AuditReportService::MODULES))
        ->and(collect($report->summary)->sum('checked'))->toBe(0);
});

test('a confirmed sale with stock booked and ledger posted has no issues', function () {
    confirmedSale();

    $report = runAudit();

    expect($report->summary['sale']['checked'])->toBe(1)
        ->and($report->issues)->toBe([]);
});

test('a confirmed paid sale with an agent commission has no issues', function () {
    confirmedSale(['agent_id' => Account::factory()->agent()->create()->id], withCommission: true);
    $paid = confirmedSale(['paid' => 1]);

    $report = runAudit();

    expect($report->summary['sale']['checked'])->toBe(2)
        ->and($paid->journal)->not->toBeNull()
        ->and($report->issues)->toBe([]);
});

test('a sale whose stock booking was removed is reported as not allocated', function () {
    $order = confirmedSale();
    Inventory::query()->update(['outbound_type' => null, 'outbound_id' => null, 'outbound_item_id' => null]);

    $report = runAudit();

    expect(failedChecks($report, 'sale'))->toContain('Inventory allocated (meters)', 'Allocation status')
        ->and($report->issues[0]['id'])->toBe($order->id)
        ->and($report->issues[0]['url'])->toBe('/sales/orders/'.$order->id);
});

test('a sale whose stock is booked to the wrong item is reported', function () {
    $order = confirmedSale();
    Inventory::query()->update(['outbound_item_id' => $order->items->first()->id + 100]);

    $report = runAudit();

    expect(failedChecks($report, 'sale'))->toBe(['Inventory linked to items']);
});

test('a sale without ledger lines for the customer or the agent commission is reported', function () {
    $order = confirmedSale(['agent_id' => Account::factory()->agent()->create()->id], withCommission: true);
    $order->journal->transactions()->where('account_id', $order->agent_id)->delete();
    $order->journal->transactions()->where('account_id', $order->customer_id)->delete();

    $report = runAudit();

    expect(failedChecks($report, 'sale'))->toContain('Customer debit', 'Agent commission credit');
});

test('a sale posted twice or never posted is reported', function () {
    $duplicated = confirmedSale();
    $missing = confirmedSale();
    Journal::query()->where('resource_id', $missing->id)->where('resource_type', Order::morphClass())->delete();
    $copy = $duplicated->journal->replicate();
    $copy->reference_no = 'JV-COPY';
    $copy->save();

    $report = runAudit();

    expect(collect($report->issues)->firstWhere('id', $duplicated->id)['check'])->toBe('Duplicate journals')
        ->and(collect($report->issues)->firstWhere('id', $missing->id)['check'])->toBe('Ledger posting');
});

test('a confirmed sale return with inventory and ledger posted has no issues, in cash and credit', function () {
    confirmedSaleReturn();
    confirmedSaleReturn(PaymentMode::Cash->value);

    $report = runAudit();

    expect($report->summary['sale_return']['checked'])->toBe(2)
        ->and($report->issues)->toBe([]);
});

test('a sale return without inventory or customer credit is reported', function () {
    $return = confirmedSaleReturn();
    Inventory::query()->delete();
    $return->journal->transactions()->delete();

    $report = runAudit();

    expect(failedChecks($report, 'sale_return'))->toContain('Inventory posting', 'Customer credit');
});

test('a sale return whose inventory meters differ from its items is reported', function () {
    confirmedSaleReturn();
    Inventory::query()->update(['meters' => 5]);

    $report = runAudit();

    expect(failedChecks($report, 'sale_return'))->toContain('Inventory meters');
});

test('a confirmed receiving with linked inventory and an invoiced purchase has no issues', function () {
    confirmedPurchase(confirmedReceiving());
    confirmedReceiving();

    $report = runAudit();

    expect($report->summary['fabric_receiving']['checked'])->toBe(2)
        ->and($report->summary['fabric_purchase']['checked'])->toBe(1)
        ->and($report->issues)->toBe([]);
});

test('a receiving whose inventory is missing, unlinked or the wrong size is reported', function () {
    $missing = confirmedReceiving();
    $unlinked = confirmedReceiving();
    $wrongMeters = confirmedReceiving();
    Inventory::query()->where('stockable_id', $missing->id)->delete();
    Inventory::query()->where('stockable_id', $unlinked->id)->update(['stockable_item_id' => 999999]);
    Inventory::query()->where('stockable_id', $wrongMeters->id)->update(['meters' => 1]);

    $report = runAudit();
    $checks = fn (FabricReceiving $stock) => collect($report->issues)->where('id', $stock->id)->pluck('check')->all();

    expect($checks($missing))->toBe(['Inventory posting'])
        ->and($checks($unlinked))->toContain('Inventory linked to items')
        ->and($checks($wrongMeters))->toContain('Inventory meters');
});

test('a receiving whose lot was cut for a sale and moved to another location still matches its items', function () {
    $stock = confirmedReceiving();
    $line = Inventory::query()->where('stockable_id', $stock->id)->sole();
    $sold = $line->meters / 4;
    $line->update(['meters' => $line->meters - $sold]);
    $cut = $line->replicate()->fill(['meters' => $sold, 'qty' => 0, 'outbound_type' => 'stock.location-transfer', 'outbound_id' => 1]);
    $cut->save();
    $line->replicate()->fill(['parent_id' => $line->id, 'location' => 'warehouse_fsd'])->save();

    $report = runAudit();

    expect($report->summary['fabric_receiving']['checked'])->toBe(1)
        ->and($report->issues)->toBe([]);
});

test('a receiving reopened by a master keeps its booked stock, but loose stock under it is an orphan', function () {
    $stock = confirmedReceiving();
    $line = Inventory::query()->where('stockable_id', $stock->id)->sole();
    $stock->update(['status' => 'Open']);
    $line->update(['outbound_type' => 'stock.location-transfer', 'outbound_id' => 1]);

    expect(runAudit()->issues)->toBe([]);

    $line->update(['outbound_type' => null, 'outbound_id' => null]);
    $report = runAudit();

    expect(failedChecks($report, 'fabric_receiving'))->toBe(['Orphaned inventory']);
});

test('a receiving with the wrong invoiced flag is reported', function () {
    $stock = confirmedReceiving();
    $invoicedByMistake = confirmedReceiving();
    $invoicedByMistake->update(['invoiced' => true]);
    $purchase = confirmedPurchase($stock);
    $stock->refresh()->update(['invoiced' => false]);

    $report = runAudit();

    expect(collect($report->issues)->where('module', 'fabric_receiving')->pluck('check', 'id')->all())
        ->toBe([$stock->id => 'Invoiced flag', $invoicedByMistake->id => 'Invoiced flag'])
        ->and(collect($report->issues)->firstWhere('id', $stock->id)['message'])->toContain($purchase->invoice_no);
});

test('a purchase with a stale stock price, un-invoiced receiving and missing ledger is reported', function () {
    $stock = confirmedReceiving();
    $purchase = confirmedPurchase($stock);
    Inventory::query()->update(['cost' => 1]);
    $stock->refresh()->update(['invoiced' => false]);
    $purchase->journal->delete();

    $report = runAudit();

    expect(failedChecks($report, 'fabric_purchase'))->toContain('Stock price updated', 'Receivings marked invoiced', 'Ledger posting');
});

test('a purchase linked to no receiving or a deleted one is reported', function () {
    $stock = confirmedReceiving();
    $purchase = confirmedPurchase($stock);
    $stock->delete();
    $orphan = confirmedPurchase(confirmedReceiving());
    $orphan->update(['stock_ids' => '']);

    $report = runAudit();

    expect(collect($report->issues)->where('id', $purchase->id)->pluck('check'))->toContain('Receivings linked')
        ->and(collect($report->issues)->where('id', $orphan->id)->pluck('check'))->toContain('Receivings linked');
});

test('purchase prices only apply to the matching product and unit', function () {
    $stock = confirmedReceiving();
    confirmedPurchase($stock, price: 3000);
    $other = Inventory::factory()->withPurchase($stock)->create(['cost' => 7]);

    $report = runAudit();

    expect($other->refresh()->cost)->toEqual(7)
        ->and(failedChecks($report, 'fabric_purchase'))->not->toContain('Stock price updated');
});

test('postings left behind by a deleted document are reported as orphans', function () {
    $order = confirmedSale();
    $order->delete();

    $report = runAudit();

    expect(collect($report->issues)->pluck('check')->unique()->sort()->values()->all())->toBe(['Orphaned inventory', 'Orphaned journal'])
        ->and(collect($report->issues)->pluck('module')->unique()->all())->toBe(['sale']);
});

test('documents outside the date range are not checked', function () {
    $this->travelTo(now()->subDays(40));
    $order = confirmedSale();
    Inventory::query()->update(['outbound_type' => null, 'outbound_id' => null, 'outbound_item_id' => null]);
    $this->travelBack();

    $outside = resolve(AuditReportService::class)->run(today()->subDays(10), today());
    $inside = resolve(AuditReportService::class)->run(today()->subDays(41), today()->subDays(39));

    expect($outside->summary['sale']['checked'])->toBe(0)
        ->and($inside->summary['sale']['checked'])->toBe(1)
        ->and($inside->issues[0]['id'])->toBe($order->id);
});

test('the report records who ran it, how and over which period', function () {
    $report = resolve(AuditReportService::class)->run(today()->subDays(2), today(), $this->user, 'web');

    expect($report->user_id)->toBe($this->user->id)
        ->and($report->source)->toBe('web')
        ->and($report->start_date->toDateString())->toBe(today()->subDays(2)->toDateString())
        ->and($report->end_date->toDateString())->toBe(today()->toDateString());
});
