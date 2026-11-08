<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\OrderAllocation;
use App\Enums\OrderPaid;
use App\Enums\OrderStatus;
use App\Enums\PackingType;
use App\Enums\PaymentMode;
use App\Enums\ReturnStatus;
use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\AuditReport;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Verifies that confirmed sales, sale returns, fabric receivings and fabric purchases have the
 * inventory movements and ledger (journal) postings their documents say they should, and stores
 * the findings as an {@see AuditReport}.
 *
 * The expected postings mirror the code that writes them:
 *  - Sale            → ConfirmOrder: ProcessSaleLedger (customer, cash, agent commission) and IssueOrderInventory
 *  - Sale return     → ConfirmSaleReturn: ProcessSaleReturnLedger and the inbound inventory lines it creates
 *  - Fabric receiving → FabricReceivingConfirmed: one inventory line per receiving item
 *  - Fabric purchase → ConfirmPurchaseActions: receivings marked invoiced, supplier ledger, UpdateStockPrice
 */
class AuditReportService
{
    public const MODULE_SALE = 'sale';

    public const MODULE_SALE_RETURN = 'sale_return';

    public const MODULE_RECEIVING = 'fabric_receiving';

    public const MODULE_PURCHASE = 'fabric_purchase';

    public const MODULES = [
        self::MODULE_SALE => 'Sale',
        self::MODULE_SALE_RETURN => 'Sale Return',
        self::MODULE_RECEIVING => 'Purchase Receiving',
        self::MODULE_PURCHASE => 'Fabric Purchase',
    ];

    public const AREA_INVENTORY = 'inventory';

    public const AREA_LEDGER = 'ledger';

    public const AREA_DOCUMENT = 'document';

    /** Money is stored as whole numbers in journals; allow rounding of fractional amounts. */
    private const AMOUNT_TOLERANCE = 1;

    private const QTY_TOLERANCE = 0.01;

    private const PRICE_TOLERANCE = 0.005;

    private const CHUNK = 1000;

    /** Longest list of items / receivings spelled out in one issue message. */
    private const MESSAGE_ITEMS = 3;

    /** @var array<int, array<string, mixed>> */
    private array $issues = [];

    /** @var array<string, array{checked: int, issues: int}> */
    private array $summary = [];

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    private ?int $cashAccountId = null;

    /** @var array<int, string|null> */
    private array $accountNames = [];

    /** @var array<int, int|null> */
    private array $expenseAccounts = [];

    /**
     * Relative link to the document, so stored reports stay valid if the domain changes.
     */
    public static function documentUrl(string $module, int $id): ?string
    {
        $route = [
            self::MODULE_SALE => 'sales.orders.show',
            self::MODULE_SALE_RETURN => 'sales.returns.show',
            self::MODULE_RECEIVING => 'purchases.fabric-receivings.show',
            self::MODULE_PURCHASE => 'purchases.pos.show',
        ][$module] ?? null;

        return $route && Route::has($route) ? route($route, $id, false) : null;
    }

    /**
     * Check every document dated (or changed) between the two dates, inclusive, and store the result.
     */
    public function run(CarbonInterface $start, CarbonInterface $end, ?User $user = null, string $source = 'console'): AuditReport
    {
        $startedAt = microtime(true);
        $this->from = CarbonImmutable::parse($start)->startOfDay();
        $this->to = CarbonImmutable::parse($end)->endOfDay();
        $this->issues = [];
        $this->summary = collect(self::MODULES)->map(fn (): array => ['checked' => 0, 'issues' => 0])->all();
        $this->cashAccountId = Account::query()
            ->where('name', 'like', '%'.AccountType::CashAccount->value.'%')
            ->orderBy('id')
            ->value('id');

        $this->checkSales();
        $this->checkSaleReturns();
        $this->checkReceivings();
        $this->checkPurchases();
        $this->checkOrphans();

        return AuditReport::query()->create([
            'start_date' => $this->from->toDateString(),
            'end_date' => $this->to->toDateString(),
            'user_id' => $user?->id,
            'source' => $source,
            'issues_count' => count($this->issues),
            'summary' => $this->summary,
            'issues' => array_values($this->issues),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    // ---------------------------------------------------------------------------------------------
    // Sales
    // ---------------------------------------------------------------------------------------------

    /**
     * A confirmed sale must have its meters booked against stock, and its customer / cash / commission ledger lines.
     */
    private function checkSales(): void
    {
        $this->documents('orders', OrderStatus::Close->value, [
            'invoice_no as ref_no', 'customer_id as party_id', 'agent_id',
            'net_total', 'total', 'paid', 'commission', 'allocation',
        ])->chunk(self::CHUNK)->each(function (Collection $orders): void {
            $ids = $orders->pluck('id');
            $items = $this->itemTotals('order_items', 'order_id', $ids, 'total_amount');
            $inventory = $this->inventoryTotals('outbound', Order::morphClass(), $ids);
            $journals = $this->journalTotals(Order::morphClass(), $ids);
            $this->loadExpenseAccounts($orders->pluck('agent_id'));

            foreach ($orders as $order) {
                $this->summary[self::MODULE_SALE]['checked']++;
                $item = $items[$order->id] ?? null;
                $booked = $inventory[$order->id] ?? null;

                if (! $item) {
                    $this->issue(self::MODULE_SALE, self::AREA_DOCUMENT, $order, 'Items', 'Confirmed sale has no items.');
                } else {
                    $this->compareAmount(self::MODULE_SALE, self::AREA_DOCUMENT, $order, 'Invoice total vs items', $item['amount'], $order->total);
                }

                $this->checkOrderAllocation($order, $item, $booked);

                $journal = $this->journalFor($journals, $order, self::MODULE_SALE);
                if (! $journal) {
                    continue;
                }
                $paid = (int) $order->paid === OrderPaid::Paid->value;
                $this->compareAmount(self::MODULE_SALE, self::AREA_LEDGER, $order, 'Customer debit', $order->net_total, $journal['party_dr']);
                $this->compareAmount(self::MODULE_SALE, self::AREA_LEDGER, $order, 'Customer credit (cash received)', $paid ? $order->net_total : 0, $journal['party_cr']);
                if ($paid && $this->cashAccountId) {
                    $this->compareAmount(self::MODULE_SALE, self::AREA_LEDGER, $order, 'Cash account debit', $order->net_total, $journal['accounts'][$this->cashAccountId]['dr'] ?? 0);
                }
                $this->checkCommission(self::MODULE_SALE, $order, $journal, (float) $order->commission, credit: true);
            }
        });
    }

    /**
     * Meters booked to the order must equal the meters its items need, item by item, and the stored status must agree.
     *
     * @param  array{rows: int, qty: float, meters: float, amount: float, lines: array<int, object>}|null  $item
     * @param  array{rows: int, qty: float, meters: float, by_item: array<int, float>}|null  $booked
     */
    private function checkOrderAllocation(object $order, ?array $item, ?array $booked): void
    {
        $required = round((float) ($item['meters'] ?? 0), 2);
        $bookedMeters = round((float) ($booked['meters'] ?? 0), 2);
        $this->compareQty(self::MODULE_SALE, self::AREA_INVENTORY, $order, 'Inventory allocated (meters)', $required, $bookedMeters);

        $expected = OrderAllocation::fromMeters($bookedMeters, $required);
        if ($expected->value !== (int) $order->allocation) {
            $stored = OrderAllocation::tryFrom((int) $order->allocation)?->label() ?? 'unknown';
            $this->issue(self::MODULE_SALE, self::AREA_INVENTORY, $order, 'Allocation status',
                "Order is marked {$stored} but its booked stock says {$expected->label()}.", $expected->value, (int) $order->allocation);
        }

        if (! $item || abs($required - $bookedMeters) > self::QTY_TOLERANCE) {
            return;
        }
        $short = collect($item['lines'])
            ->map(fn (object $line): array => [
                'id' => $line->id,
                'expected' => round((float) $line->total_qty, 2),
                'actual' => round((float) ($booked['by_item'][$line->id] ?? 0), 2),
            ])
            ->filter(fn (array $line): bool => abs($line['expected'] - $line['actual']) > self::QTY_TOLERANCE);
        if ($short->isNotEmpty()) {
            $this->issue(self::MODULE_SALE, self::AREA_INVENTORY, $order, 'Inventory linked to items',
                'Booked stock does not follow the items: '.$this->describeMismatches($short).'.', 0, $short->count());
        }
    }

    /**
     * A confirmed sale return must bring its meters back into stock and post the customer / cash / commission lines.
     */
    private function checkSaleReturns(): void
    {
        $this->documents('sales_returns', ReturnStatus::Closed->value, [
            'invoice_no as ref_no', 'customer_id as party_id', 'agent_id',
            'total_amount', 'total_qty', 'payment_mode', 'commission',
        ])->chunk(self::CHUNK)->each(function (Collection $returns): void {
            $ids = $returns->pluck('id');
            $items = $this->itemTotals('sales_return_items', 'sales_return_id', $ids, 'total_amount');
            $inventory = $this->inventoryTotals('stockable', SalesReturn::morphClass(), $ids);
            $journals = $this->journalTotals(SalesReturn::morphClass(), $ids);
            $this->loadExpenseAccounts($returns->pluck('agent_id'));

            foreach ($returns as $return) {
                $this->summary[self::MODULE_SALE_RETURN]['checked']++;
                $item = $items[$return->id] ?? null;

                if (! $item) {
                    $this->issue(self::MODULE_SALE_RETURN, self::AREA_DOCUMENT, $return, 'Items', 'Confirmed sale return has no items.');
                } else {
                    $this->compareAmount(self::MODULE_SALE_RETURN, self::AREA_DOCUMENT, $return, 'Return total vs items', $item['amount'], $return->total_amount);
                    $this->compareQty(self::MODULE_SALE_RETURN, self::AREA_DOCUMENT, $return, 'Return meters vs items', $item['meters'], $return->total_qty);
                }

                $this->compareInventory(self::MODULE_SALE_RETURN, $return, $item, $inventory[$return->id] ?? null);

                $journal = $this->journalFor($journals, $return, self::MODULE_SALE_RETURN);
                if (! $journal) {
                    continue;
                }
                $cash = strcasecmp((string) $return->payment_mode, PaymentMode::Cash->value) === 0;
                $this->compareAmount(self::MODULE_SALE_RETURN, self::AREA_LEDGER, $return, 'Customer credit', $return->total_amount, $journal['party_cr']);
                $this->compareAmount(self::MODULE_SALE_RETURN, self::AREA_LEDGER, $return, 'Customer debit (cash refund)', $cash ? $return->total_amount : 0, $journal['party_dr']);
                if ($cash && $this->cashAccountId) {
                    $this->compareAmount(self::MODULE_SALE_RETURN, self::AREA_LEDGER, $return, 'Cash account credit', $return->total_amount, $journal['accounts'][$this->cashAccountId]['cr'] ?? 0);
                }
                $this->checkCommission(self::MODULE_SALE_RETURN, $return, $journal, (float) $return->commission, credit: false);
            }
        });
    }

    /**
     * The agent is credited the commission (and the agent's expense account debited) on a sale; a return reverses both.
     *
     * @param  array{party_dr: float, party_cr: float, accounts: array<int, array{dr: float, cr: float}>}  $journal
     */
    private function checkCommission(string $module, object $doc, array $journal, float $commission, bool $credit): void
    {
        if (! $doc->agent_id || (int) $doc->agent_id === (int) $doc->party_id) {
            return;
        }
        $expected = $commission > 0 ? $commission : 0;
        $agent = $journal['accounts'][$doc->agent_id] ?? ['dr' => 0, 'cr' => 0];
        $label = $credit ? 'credit' : 'debit';
        $this->compareAmount($module, self::AREA_LEDGER, $doc, "Agent commission {$label}", $expected, $credit ? $agent['cr'] : $agent['dr']);

        $expenseAccount = $this->expenseAccounts[(int) $doc->agent_id] ?? null;
        if ($expenseAccount) {
            $expense = $journal['accounts'][$expenseAccount] ?? ['dr' => 0, 'cr' => 0];
            $this->compareAmount($module, self::AREA_LEDGER, $doc, 'Commission expense '.($credit ? 'debit' : 'credit'), $expected, $credit ? $expense['dr'] : $expense['cr']);
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Purchases
    // ---------------------------------------------------------------------------------------------

    /**
     * A confirmed receiving must have one inventory line per item, linked to it and carrying its meters.
     * It must be marked invoiced exactly when a confirmed purchase voucher lists it.
     */
    private function checkReceivings(): void
    {
        $invoicedOn = $this->confirmedPurchasesByReceiving();

        $this->documents('fabric_receivings', StatusText::Close->value, [
            'invoice_no as ref_no', 'supplier_id as party_id', 'invoiced',
        ])->chunk(self::CHUNK)->each(function (Collection $receivings) use ($invoicedOn): void {
            $ids = $receivings->pluck('id');
            $items = $this->itemTotals('fabric_receiving_items', 'fabric_receiving_id', $ids);
            $inventory = $this->inventoryTotals('stockable', FabricReceiving::morphClass(), $ids);

            foreach ($receivings as $receiving) {
                $this->summary[self::MODULE_RECEIVING]['checked']++;
                $item = $items[$receiving->id] ?? null;
                if (! $item) {
                    $this->issue(self::MODULE_RECEIVING, self::AREA_DOCUMENT, $receiving, 'Items', 'Confirmed receiving has no items.');
                }
                $this->compareInventory(self::MODULE_RECEIVING, $receiving, $item, $inventory[$receiving->id] ?? null);

                $vouchers = $invoicedOn[$receiving->id] ?? [];
                $expected = $vouchers === [] ? 0 : 1;
                if ((int) $receiving->invoiced !== $expected) {
                    $this->issue(self::MODULE_RECEIVING, self::AREA_DOCUMENT, $receiving, 'Invoiced flag',
                        $expected
                            ? 'Receiving is on confirmed purchase '.implode(', ', $vouchers).' but is not marked invoiced.'
                            : 'Receiving is marked invoiced but is on no confirmed purchase.',
                        $expected, (int) $receiving->invoiced);
                }
            }
        });
    }

    /**
     * A confirmed purchase must be linked to confirmed, invoiced receivings whose stock carries the purchase prices,
     * and must credit the supplier its total.
     */
    private function checkPurchases(): void
    {
        $this->documents('purchases', StatusText::Close->value, [
            'invoice_no as ref_no', 'supplier_id as party_id', 'total', 'stock_ids',
        ])->chunk(self::CHUNK)->each(function (Collection $purchases): void {
            $ids = $purchases->pluck('id');
            $items = $this->itemTotals('purchase_items', 'purchase_id', $ids, 'total', ['product_id', 'unit', 'size', 'price']);
            $journals = $this->journalTotals(Purchase::morphClass(), $ids);

            $receivingIds = $purchases->flatMap(fn (object $purchase): array => $this->receivingIds($purchase))->unique()->values();
            $receivings = DB::table('fabric_receivings')
                ->whereIn('id', $receivingIds)
                ->get(['id', 'invoice_no', 'status', 'invoiced', 'deleted_at'])
                ->keyBy('id');
            $stock = DB::table('inventories')
                ->where('stockable_type', FabricReceiving::morphClass())
                ->whereIn('stockable_id', $receivingIds)
                ->get(['stockable_id', 'product_id', 'unit', 'size', 'cost'])
                ->groupBy('stockable_id');

            foreach ($purchases as $purchase) {
                $this->summary[self::MODULE_PURCHASE]['checked']++;
                $item = $items[$purchase->id] ?? null;

                if (! $item) {
                    $this->issue(self::MODULE_PURCHASE, self::AREA_DOCUMENT, $purchase, 'Items', 'Confirmed purchase has no items.');
                } else {
                    $this->compareAmount(self::MODULE_PURCHASE, self::AREA_DOCUMENT, $purchase, 'Purchase total vs items', $item['amount'], $purchase->total);
                }

                $linked = $this->checkPurchaseReceivings($purchase, $receivings);
                $this->checkPurchaseStock($purchase, $item, $linked, $stock);

                $journal = $this->journalFor($journals, $purchase, self::MODULE_PURCHASE);
                if (! $journal) {
                    continue;
                }
                $this->compareAmount(self::MODULE_PURCHASE, self::AREA_LEDGER, $purchase, 'Supplier credit', $purchase->total, $journal['party_cr']);
                $this->compareAmount(self::MODULE_PURCHASE, self::AREA_LEDGER, $purchase, 'Supplier debit', 0, $journal['party_dr']);
            }
        });
    }

    /**
     * The purchase's receivings must exist, be confirmed and be marked invoiced. Returns the usable ones.
     *
     * @param  Collection<int, object>  $receivings
     * @return Collection<int, object>
     */
    private function checkPurchaseReceivings(object $purchase, Collection $receivings): Collection
    {
        $linkedIds = $this->receivingIds($purchase);
        if ($linkedIds === []) {
            $this->issue(self::MODULE_PURCHASE, self::AREA_INVENTORY, $purchase, 'Receivings linked', 'No fabric receiving is linked to this purchase.', 1, 0);

            return collect();
        }

        $usable = collect($linkedIds)
            ->map(fn (int $id): ?object => $receivings->get($id))
            ->filter(fn (?object $receiving): bool => $receiving !== null
                && $receiving->deleted_at === null
                && $receiving->status === StatusText::Close->value);
        if ($usable->count() !== count($linkedIds)) {
            $this->issue(self::MODULE_PURCHASE, self::AREA_INVENTORY, $purchase, 'Receivings linked',
                'Some linked receivings are missing, deleted or not confirmed (ids '.implode(', ', $linkedIds).').', count($linkedIds), $usable->count());
        }

        $notInvoiced = $usable->reject(fn (object $receiving): bool => (bool) $receiving->invoiced);
        if ($notInvoiced->isNotEmpty()) {
            $this->issue(self::MODULE_PURCHASE, self::AREA_DOCUMENT, $purchase, 'Receivings marked invoiced',
                'Not marked invoiced: '.$notInvoiced->pluck('invoice_no')->take(self::MESSAGE_ITEMS)->implode(', ').'.', 0, $notInvoiced->count());
        }

        return $usable;
    }

    /**
     * Every linked receiving must hold stock, every purchase item must find its stock lines, and those lines must
     * carry the item price (the last item wins when a product repeats, as UpdateStockPrice applies them in order).
     *
     * @param  array{rows: int, qty: float, meters: float, amount: float, lines: array<int, object>}|null  $item
     * @param  Collection<int, object>  $linked
     * @param  Collection<int|string, Collection<int, object>>  $stock
     */
    private function checkPurchaseStock(object $purchase, ?array $item, Collection $linked, Collection $stock): void
    {
        if ($linked->isEmpty()) {
            return;
        }
        $withoutStock = $linked->filter(fn (object $receiving): bool => ! $stock->has($receiving->id));
        $this->compareQty(self::MODULE_PURCHASE, self::AREA_INVENTORY, $purchase, 'Receivings with inventory', $linked->count(), $linked->count() - $withoutStock->count());
        if (! $item) {
            return;
        }

        $lines = $linked->flatMap(fn (object $receiving): Collection => $stock->get($receiving->id, collect()));
        $prices = collect($item['lines'])->keyBy(fn (object $line): string => $this->stockKey($line));
        $matched = 0;
        $correct = 0;
        $total = 0;
        $wrong = collect();
        foreach ($prices as $line) {
            $matches = $lines->filter(fn (object $stockLine): bool => (int) $stockLine->product_id === (int) $line->product_id
                && $stockLine->unit === $line->unit
                && ($line->unit !== PackingType::Box->value || abs((float) $stockLine->size - (float) $line->size) < 0.001));
            $matched += $matches->isNotEmpty() ? 1 : 0;
            foreach ($matches as $stockLine) {
                $total++;
                if ($stockLine->cost !== null && abs((float) $stockLine->cost - (float) $line->price) <= self::PRICE_TOLERANCE) {
                    $correct++;
                } else {
                    $wrong->push("product #{$line->product_id} cost ".($stockLine->cost ?? 'none')." (price {$line->price})");
                }
            }
        }

        $this->compareQty(self::MODULE_PURCHASE, self::AREA_INVENTORY, $purchase, 'Purchase items found in stock', $prices->count(), $matched);
        if ($correct !== $total) {
            $this->issue(self::MODULE_PURCHASE, self::AREA_INVENTORY, $purchase, 'Stock price updated',
                'Stock lines not at the purchase price: '.$wrong->take(self::MESSAGE_ITEMS)->implode('; ').'.', $total, $correct);
        }
    }

    /**
     * @return array<int, string>
     */
    private function receivingIds(object $purchase): array
    {
        return array_values(array_unique(array_map('intval', array_filter(explode(',', (string) $purchase->stock_ids)))));
    }

    /**
     * Purchase items that price the same stock: same product and unit, and the same size for boxes.
     */
    private function stockKey(object $line): string
    {
        return $line->product_id.'|'.$line->unit.'|'.($line->unit === PackingType::Box->value ? round((float) $line->size, 2) : '');
    }

    /**
     * Receiving id => invoice numbers of the confirmed purchase vouchers that list it.
     *
     * @return array<int, array<int, string>>
     */
    private function confirmedPurchasesByReceiving(): array
    {
        $map = [];
        DB::table('purchases')
            ->whereNull('deleted_at')
            ->where('status', StatusText::Close->value)
            ->whereNotNull('stock_ids')
            ->get(['id', 'invoice_no', 'stock_ids'])
            ->each(function (object $purchase) use (&$map): void {
                foreach ($this->receivingIds($purchase) as $receivingId) {
                    $map[$receivingId][] = $purchase->invoice_no ?? '#'.$purchase->id;
                }
            });

        return $map;
    }

    // ---------------------------------------------------------------------------------------------
    // Orphans: postings whose document is missing, deleted or no longer confirmed
    // ---------------------------------------------------------------------------------------------

    private function checkOrphans(): void
    {
        $inventories = [
            [self::MODULE_SALE, 'outbound', Order::morphClass(), 'orders', OrderStatus::Close->value, 'invoice_no'],
            [self::MODULE_SALE_RETURN, 'stockable', SalesReturn::morphClass(), 'sales_returns', ReturnStatus::Closed->value, 'invoice_no'],
            [self::MODULE_RECEIVING, 'stockable', FabricReceiving::morphClass(), 'fabric_receivings', StatusText::Close->value, 'invoice_no'],
        ];
        foreach ($inventories as [$module, $prefix, $morph, $table, $closed, $refColumn]) {
            // A master reopen keeps the stock sold from a receiving booked until it is confirmed again.
            $keepsBookedStock = $module === self::MODULE_RECEIVING;
            $deletedColumn = Schema::hasColumn($table, 'deleted_at');
            DB::table('inventories as i')
                ->leftJoin("{$table} as d", 'd.id', '=', "i.{$prefix}_id")
                ->where("i.{$prefix}_type", $morph)
                ->whereBetween('i.created_at', [$this->from, $this->to])
                ->where(fn ($query) => $query->whereNull('d.id')
                    ->when($deletedColumn, fn ($query) => $query->orWhereNotNull('d.deleted_at'))
                    ->orWhere(fn ($query) => $query->where('d.status', '<>', $closed)
                        ->when($keepsBookedStock, fn ($query) => $query->whereNull('i.outbound_id'))))
                ->groupBy("i.{$prefix}_id", 'd.id', "d.{$refColumn}", 'd.status')
                ->select(array_filter([
                    "i.{$prefix}_id as id",
                    "d.{$refColumn} as ref_no",
                    'd.id as doc_id',
                    'd.status',
                    $deletedColumn ? DB::raw('MAX(d.deleted_at) as deleted_at') : null,
                    DB::raw('SUM(i.meters) as meters'),
                    DB::raw('MAX(i.created_at) as posted_at'),
                ]))
                ->get()
                ->each(fn (object $row) => $this->issue($module, self::AREA_INVENTORY,
                    (object) ['id' => $row->id, 'ref_no' => $row->ref_no, 'party_id' => null, 'transaction_date' => null, 'created_at' => $row->posted_at],
                    'Orphaned inventory', $this->orphanReason($row).' Inventory still holds '.round((float) $row->meters, 2).' meters for it.', 0, (float) $row->meters));
        }

        $journals = [
            [self::MODULE_SALE, Order::morphClass(), 'orders', OrderStatus::Close->value, 'invoice_no'],
            [self::MODULE_SALE_RETURN, SalesReturn::morphClass(), 'sales_returns', ReturnStatus::Closed->value, 'invoice_no'],
            [self::MODULE_PURCHASE, Purchase::morphClass(), 'purchases', StatusText::Close->value, 'invoice_no'],
        ];
        $journalsSoftDeleted = Schema::hasColumn('journals', 'deleted_at');
        foreach ($journals as [$module, $morph, $table, $closed, $refColumn]) {
            $deletedColumn = Schema::hasColumn($table, 'deleted_at');
            DB::table('journals as j')
                ->leftJoin("{$table} as d", 'd.id', '=', 'j.resource_id')
                ->leftJoin('journal_details as jd', fn ($join) => $join->on('jd.journal_id', '=', 'j.id')
                    ->when($journalsSoftDeleted, fn ($join) => $join->whereNull('jd.deleted_at')))
                ->where('j.resource_type', $morph)
                ->when($journalsSoftDeleted, fn ($query) => $query->whereNull('j.deleted_at'))
                ->whereBetween('j.created_at', [$this->from, $this->to])
                ->where(fn ($query) => $query->whereNull('d.id')
                    ->when($deletedColumn, fn ($query) => $query->orWhereNotNull('d.deleted_at'))
                    ->orWhere('d.status', '<>', $closed))
                ->groupBy('j.resource_id', 'd.id', "d.{$refColumn}", 'd.status')
                ->select(array_filter([
                    'j.resource_id as id',
                    "d.{$refColumn} as ref_no",
                    'd.id as doc_id',
                    'd.status',
                    $deletedColumn ? DB::raw('MAX(d.deleted_at) as deleted_at') : null,
                    DB::raw('GROUP_CONCAT(DISTINCT j.reference_no) as vouchers'),
                    DB::raw('COALESCE(SUM(jd.dr), 0) as dr'),
                    DB::raw('MAX(j.created_at) as posted_at'),
                ]))
                ->get()
                ->each(fn (object $row) => $this->issue($module, self::AREA_LEDGER,
                    (object) ['id' => $row->id, 'ref_no' => $row->ref_no, 'party_id' => null, 'transaction_date' => null, 'created_at' => $row->posted_at],
                    'Orphaned journal', $this->orphanReason($row).' Journal '.$row->vouchers.' is still posted.', 0, (float) $row->dr));
        }
    }

    private function orphanReason(object $row): string
    {
        return match (true) {
            $row->doc_id === null => 'Document no longer exists.',
            ($row->deleted_at ?? null) !== null => 'Document is deleted.',
            default => 'Document is not confirmed (status: '.$row->status.').',
        };
    }

    // ---------------------------------------------------------------------------------------------
    // Data loaders
    // ---------------------------------------------------------------------------------------------

    /**
     * Confirmed, non-deleted documents dated in the range or changed in it.
     *
     * @param  array<int, string>  $columns
     * @return Collection<int, object>
     */
    private function documents(string $table, int|string $closedStatus, array $columns): Collection
    {
        return DB::table($table)
            ->select(array_merge(['id', 'transaction_date', 'created_at'], $columns))
            ->when(Schema::hasColumn($table, 'deleted_at'), fn ($query) => $query->whereNull('deleted_at'))
            ->where('status', $closedStatus)
            ->where(fn ($query) => $query
                ->whereBetween('transaction_date', [$this->from, $this->to])
                ->orWhereBetween('updated_at', [$this->from, $this->to]))
            ->orderBy('id')
            ->get();
    }

    /**
     * Item rows per document: counts, totals and the lines themselves (ids plus the requested extra columns).
     *
     * @param  array<int, string>  $extraColumns
     * @return array<int, array{rows: int, qty: float, meters: float, amount: float, lines: array<int, object>}>
     */
    private function itemTotals(string $table, string $foreignKey, Collection $ids, ?string $amountColumn = null, array $extraColumns = []): array
    {
        return DB::table($table)
            ->whereIn($foreignKey, $ids)
            ->orderBy('id')
            ->get(array_merge(['id', "{$foreignKey} as document_id", 'qty', 'total_qty'], $amountColumn ? ["{$amountColumn} as amount"] : [], $extraColumns))
            ->groupBy('document_id')
            ->map(fn (Collection $lines): array => [
                'rows' => $lines->count(),
                'qty' => (float) $lines->sum('qty'),
                'meters' => (float) $lines->sum('total_qty'),
                'amount' => $amountColumn ? (float) $lines->sum('amount') : 0.0,
                'lines' => $lines->all(),
            ])
            ->all();
    }

    /**
     * Inventory lines per document, plus the meters each document item received or had booked.
     * Lots opened at a destination by a stock transfer (parent_id) are left out: their source line still holds the meters.
     *
     * @param  'stockable'|'outbound'  $prefix
     * @return array<int, array{rows: int, qty: float, meters: float, by_item: array<int, float>}>
     */
    private function inventoryTotals(string $prefix, string $morph, Collection $ids): array
    {
        return DB::table('inventories')
            ->where("{$prefix}_type", $morph)
            ->whereIn("{$prefix}_id", $ids)
            ->when($prefix === 'stockable', fn ($query) => $query->whereNull('parent_id'))
            ->get(["{$prefix}_id as document_id", $prefix === 'stockable' ? 'stockable_item_id as item_id' : 'outbound_item_id as item_id', 'qty', 'meters'])
            ->groupBy('document_id')
            ->map(fn (Collection $lines): array => [
                'rows' => $lines->count(),
                'qty' => (float) $lines->sum('qty'),
                'meters' => (float) $lines->sum('meters'),
                'by_item' => $lines->whereNotNull('item_id')->groupBy('item_id')->map(fn (Collection $group): float => (float) $group->sum('meters'))->all(),
            ])
            ->all();
    }

    /**
     * Journals posted against each document, with debit / credit totals per account.
     *
     * @return array<int, array{journals: array<int, string>, accounts: array<int, array{dr: float, cr: float}>}>
     */
    private function journalTotals(string $morph, Collection $ids): array
    {
        $softDeleted = Schema::hasColumn('journals', 'deleted_at');
        $result = [];
        DB::table('journals as j')
            ->leftJoin('journal_details as d', fn ($join) => $join->on('d.journal_id', '=', 'j.id')
                ->when($softDeleted, fn ($join) => $join->whereNull('d.deleted_at')))
            ->where('j.resource_type', $morph)
            ->whereIn('j.resource_id', $ids)
            ->when($softDeleted, fn ($query) => $query->whereNull('j.deleted_at'))
            ->groupBy('j.resource_id', 'j.id', 'j.reference_no', 'd.account_id')
            ->select(['j.resource_id', 'j.id', 'j.reference_no', 'd.account_id', DB::raw('COALESCE(SUM(d.dr), 0) as dr'), DB::raw('COALESCE(SUM(d.cr), 0) as cr')])
            ->get()
            ->each(function (object $row) use (&$result): void {
                $result[$row->resource_id]['journals'][$row->id] = $row->reference_no;
                if ($row->account_id !== null) {
                    $account = $result[$row->resource_id]['accounts'][$row->account_id] ?? ['dr' => 0, 'cr' => 0];
                    $result[$row->resource_id]['accounts'][$row->account_id] = [
                        'dr' => $account['dr'] + (float) $row->dr,
                        'cr' => $account['cr'] + (float) $row->cr,
                    ];
                }
            });

        return $result;
    }

    /**
     * @param  Collection<int, int|string|null>  $agentIds
     */
    private function loadExpenseAccounts(Collection $agentIds): void
    {
        $missing = $agentIds->filter()->unique()->reject(fn ($id): bool => array_key_exists((int) $id, $this->expenseAccounts));
        if ($missing->isEmpty()) {
            return;
        }
        foreach (DB::table('accounts')->whereIn('id', $missing)->pluck('expense_account', 'id') as $id => $expenseAccount) {
            $this->expenseAccounts[(int) $id] = $expenseAccount ? (int) $expenseAccount : null;
        }
    }

    // ---------------------------------------------------------------------------------------------
    // Comparisons
    // ---------------------------------------------------------------------------------------------

    /**
     * Reports missing / duplicate journals; returns party totals when there is something to compare.
     *
     * @param  array<int, array{journals: array<int, string>, accounts: array<int, array{dr: float, cr: float}>}>  $journals
     * @return array{party_dr: float, party_cr: float, accounts: array<int, array{dr: float, cr: float}>}|null
     */
    private function journalFor(array $journals, object $doc, string $module): ?array
    {
        $label = mb_strtolower(self::MODULES[$module]);
        $entry = $journals[$doc->id] ?? null;

        if (! $entry) {
            $this->issue($module, self::AREA_LEDGER, $doc, 'Ledger posting', "No journal entry found for this {$label}.");

            return null;
        }

        $count = count($entry['journals']);
        if ($count > 1) {
            $this->issue($module, self::AREA_LEDGER, $doc, 'Duplicate journals',
                "{$count} journals are posted for this {$label} (".implode(', ', $entry['journals']).'); amounts are counted more than once.', 1, $count);
        }

        $accounts = $entry['accounts'] ?? [];

        return [
            'party_dr' => $accounts[$doc->party_id]['dr'] ?? 0,
            'party_cr' => $accounts[$doc->party_id]['cr'] ?? 0,
            'accounts' => $accounts,
        ];
    }

    /**
     * Stock that comes in (sale returns, receivings) must reach inventory item by item: every item has a line linked to
     * it, and the lines of each item add up to its meters. Lines split when a lot is cut for a sale or moved, so counts
     * and pieces only match until then.
     *
     * @param  array{rows: int, qty: float, meters: float, amount: float, lines: array<int, object>}|null  $items
     * @param  array{rows: int, qty: float, meters: float, by_item: array<int, float>}|null  $stock
     */
    private function compareInventory(string $module, object $doc, ?array $items, ?array $stock): void
    {
        if (! $stock) {
            $this->issue($module, self::AREA_INVENTORY, $doc, 'Inventory posting', 'No inventory lines found for this document.', round((float) ($items['meters'] ?? 0), 2), 0);

            return;
        }
        if (! $items) {
            return;
        }

        $linked = collect($items['lines'])->filter(fn (object $line): bool => isset($stock['by_item'][$line->id]))->count();
        $this->compareQty($module, self::AREA_INVENTORY, $doc, 'Inventory linked to items', $items['rows'], $linked);
        if ($stock['rows'] === $items['rows']) { // pieces are only comparable until a lot is cut or split
            $pieces = collect($items['lines'])->sum(fn (object $line): int => (int) round((float) $line->qty));
            $this->compareQty($module, self::AREA_INVENTORY, $doc, 'Inventory pieces', $pieces, $stock['qty']);
        }
        $this->compareQty($module, self::AREA_INVENTORY, $doc, 'Inventory meters', $items['meters'], $stock['meters']);

        if (abs($items['meters'] - $stock['meters']) > self::QTY_TOLERANCE) {
            return;
        }
        $different = collect($items['lines'])
            ->map(fn (object $line): array => [
                'id' => $line->id,
                'expected' => round((float) $line->total_qty, 2),
                'actual' => round((float) ($stock['by_item'][$line->id] ?? 0), 2),
            ])
            ->filter(fn (array $line): bool => abs($line['expected'] - $line['actual']) > self::QTY_TOLERANCE);
        if ($different->isNotEmpty()) {
            $this->issue($module, self::AREA_INVENTORY, $doc, 'Inventory meters per item',
                'Inventory does not follow the items: '.$this->describeMismatches($different).'.', 0, $different->count());
        }
    }

    /**
     * @param  Collection<int, array{id: int, expected: float, actual: float}>  $lines
     */
    private function describeMismatches(Collection $lines): string
    {
        return $lines->take(self::MESSAGE_ITEMS)
            ->map(fn (array $line): string => "item #{$line['id']} needs {$line['expected']}m, has {$line['actual']}m")
            ->implode('; ').($lines->count() > self::MESSAGE_ITEMS ? '; …' : '');
    }

    private function compareQty(string $module, string $area, object $doc, string $check, float|int|string|null $expected, float|int|string|null $actual): void
    {
        if (abs((float) $expected - (float) $actual) > self::QTY_TOLERANCE) {
            $this->issue($module, $area, $doc, $check, null, round((float) $expected, 2), round((float) $actual, 2));
        }
    }

    private function compareAmount(string $module, string $area, object $doc, string $check, float|int|string|null $expected, float|int|string|null $actual): void
    {
        if (abs((float) $expected - (float) $actual) > self::AMOUNT_TOLERANCE) {
            $this->issue($module, $area, $doc, $check, null, round((float) $expected, 2), round((float) $actual, 2));
        }
    }

    private function issue(string $module, string $area, object $doc, string $check, ?string $message = null, float|int|null $expected = null, float|int|null $actual = null): void
    {
        $this->summary[$module]['issues']++;

        $date = $doc->transaction_date ?? $doc->created_at ?? null;
        $this->issues[] = [
            'module' => $module,
            'module_label' => self::MODULES[$module],
            'area' => $area,
            'id' => (int) $doc->id,
            'ref_no' => $doc->ref_no ?? null,
            'party' => ($doc->party_id ?? null) ? $this->accountName((int) $doc->party_id) : null,
            'date' => $date ? CarbonImmutable::parse($date)->toDateString() : null,
            'check' => $check,
            'expected' => $expected,
            'actual' => $actual,
            'difference' => $expected !== null && $actual !== null ? round($actual - $expected, 2) : null,
            'message' => $message ?? "{$check}: expected {$expected}, found {$actual}.",
            'url' => self::documentUrl($module, (int) $doc->id),
        ];
    }

    private function accountName(int $id): ?string
    {
        return $this->accountNames[$id] ??= DB::table('accounts')->where('id', $id)->value('name');
    }
}
