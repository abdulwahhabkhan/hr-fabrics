<?php

namespace Database\Seeders;

use App\Actions\Inbound\FabricReceiving\FabricReceivingConfirmed;
use App\Actions\Inbound\FabricReceiving\UpdateFabricReceivingTotal;
use App\Actions\Inbound\Purchase\ConfirmPurchaseActions;
use App\Actions\Inbound\Purchase\CreatePurchase;
use App\Actions\Inbound\Purchase\UpdatePurchaseTotal;
use App\Actions\Inbound\Return\ConfirmReturn;
use App\Actions\Inbound\Return\UpdateReturnTotal;
use App\Actions\LogAction\RecordAction;
use App\Enums\PackingType;
use App\Enums\ReturnStatus;
use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Stock\Inventory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

/**
 * Seeds confirmed fabric receivings (purchase receipts), purchases (vouchers) and
 * purchase returns dated across last month and today, so inbound reports have data.
 *
 * Reuses the users, suppliers and products created by BaseSeeder.
 */
class InboundSeeder extends Seeder
{
    use SeedsTransactions;

    private const int RECEIVINGS_LAST_MONTH = 12;

    private const int RECEIVINGS_TODAY = 3;

    private const int RETURNS_LAST_MONTH = 4;

    private const int RETURNS_TODAY = 1;

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        $receivings = $this->seedFabricReceivings();
        $this->seedPurchases($receivings);
        $this->seedPurchaseReturns();
    }

    /**
     * Confirmed fabric receivings; confirming books the inventory.
     *
     * @return Collection<int, FabricReceiving>
     *
     * @throws Throwable
     */
    private function seedFabricReceivings(): Collection
    {
        $dates = $this->transactionDates(self::RECEIVINGS_LAST_MONTH, self::RECEIVINGS_TODAY);

        return $dates->map(function (CarbonImmutable $date): FabricReceiving {
            return DB::transaction(function () use ($date): FabricReceiving {
                /** @var User $user */
                $user = $this->getUsers()->random();
                $serial = (int) FabricReceiving::query()->max('id') + 1;

                $receiving = FabricReceiving::factory()
                    ->recycle($this->getUsers())
                    ->recycle($this->getSuppliers())
                    ->create([
                        'invoice_no' => 'ASN-'.$date->format('ym').str($serial)->padLeft(3, '0'),
                        'created_by' => $user->id,
                    ]);

                foreach (range(1, random_int(1, 4)) as $ignored) {
                    $receiving->items()->create($this->receivingItemAttributes());
                }

                $receiving->fill([
                    'status' => StatusText::Close,
                    'transaction_date' => $date,
                ])->save();

                resolve(UpdateFabricReceivingTotal::class)->handle($receiving);
                resolve(FabricReceivingConfirmed::class)->handle($receiving);
                resolve(RecordAction::class)->handle($receiving, $user, 'Stock receiving confirmed');

                $this->stampTimestamps($receiving, $date);

                return $receiving;
            });
        });
    }

    /**
     * Purchase vouchers priced from the receivings; a few receivings stay uninvoiced.
     *
     * @param  Collection<int, FabricReceiving>  $receivings
     * @return Collection<int, Purchase>
     *
     * @throws Throwable
     */
    private function seedPurchases(Collection $receivings): Collection
    {
        return $receivings
            ->reject(fn (FabricReceiving $receiving, int $index) => $index % 4 === 3)
            ->map(function (FabricReceiving $receiving): Purchase {
                return DB::transaction(function () use ($receiving): Purchase {
                    /** @var User $user */
                    $user = $this->getUsers()->random();
                    $date = $this->clampToToday(
                        CarbonImmutable::parse($receiving->transaction_date)->addDays(random_int(0, 2))
                    );

                    $purchase = resolve(CreatePurchase::class)->handle([
                        'stock' => [
                            [
                                'id' => $receiving->id,
                                'supplier_id' => $receiving->supplier_id,
                                'bilti_no' => $receiving->bilti_no,
                                'invoice_no' => $receiving->invoice_no,
                                'lot_no' => $receiving->lot_no,
                            ],
                        ],
                    ], $user);

                    $purchase->items->each(function (PurchaseItem $item): void {
                        $price = $item->isBox() ? random_int(2000, 5000) : random_int(100, 500);
                        $item->update([
                            'price' => $price,
                            'total' => ($item->isBox() ? $item->qty : $item->total_qty) * $price,
                        ]);
                    });

                    $purchase->fill([
                        'bill_no' => 'B-'.str($purchase->id)->padLeft(4, '0'),
                        'status' => StatusText::Close,
                        'transaction_date' => $date,
                    ])->save();

                    resolve(UpdatePurchaseTotal::class)->handle($purchase);
                    resolve(ConfirmPurchaseActions::class)->handle($purchase->refresh(), $user);

                    $this->stampTimestamps($purchase, $date);

                    return $purchase;
                });
            })
            ->values();
    }

    /**
     * Confirmed returns issued against stock still available in inventory.
     *
     * @throws Throwable
     */
    private function seedPurchaseReturns(): void
    {
        $lastMonthReturns = self::RETURNS_LAST_MONTH;
        $total = $lastMonthReturns + self::RETURNS_TODAY;

        foreach (range(1, $total) as $number) {
            $isToday = $number > $lastMonthReturns;

            DB::transaction(function () use ($isToday): void {
                /** @var Collection<int, Inventory> $inventories */
                $inventories = $this->pickReturnableInventories();
                if ($inventories->isEmpty()) {
                    return;
                }
                /** @var User $user */
                $user = $this->getUsers()->random();
                $supplierId = $inventories->first()->stockable->supplier_id;
                $date = $isToday
                    ? CarbonImmutable::today()
                    : $this->clampToToday(
                        CarbonImmutable::parse($inventories->max('transaction_date'))->addDays(random_int(0, 3))
                    );
                $serial = (int) PurchaseReturn::query()->max('sr') + 1;

                $return = PurchaseReturn::factory()
                    ->recycle($this->getUsers())
                    ->create([
                        'sr' => $serial,
                        'invoice_no' => 'POR-'.$date->format('ym').str($serial)->padLeft(3, '0'),
                        'supplier_id' => $supplierId,
                        'created_by' => $user->id,
                        'total_qty' => 0,
                        'total_amount' => 0,
                    ]);

                foreach ($inventories as $inventory) {
                    $return->items()->create($this->returnItemAttributes($inventory));
                }

                $return->fill([
                    'status' => ReturnStatus::Closed,
                    'transaction_date' => $date,
                ])->save();

                resolve(UpdateReturnTotal::class)->handle($return);
                resolve(ConfirmReturn::class)->handle($return->refresh(), $user);

                $this->stampTimestamps($return, $date);
            });
        }
    }

    /**
     * @throws RandomException
     */
    private function pickReturnableInventories(): Collection
    {
        $first = Inventory::query()
            ->available()
            ->where('stockable_type', FabricReceiving::morphClass())
            ->whereNotNull('cost')
            ->with('stockable')
            ->inRandomOrder()
            ->first();

        if (! $first) {
            return collect();
        }

        /** @var Collection<int, Inventory> */
        return Inventory::query()
            ->available()
            ->where('stockable_type', FabricReceiving::morphClass())
            ->whereNotNull('cost')
            ->whereIn(
                'stockable_id',
                FabricReceiving::query()->where('supplier_id', $first->stockable->supplier_id)->select('id')
            )
            ->with('stockable')
            ->inRandomOrder()
            ->limit(random_int(1, 2))
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function receivingItemAttributes(): array
    {
        /** @var Product $product */
        $product = $this->getProducts()->random();
        $attributes = [
            'product_id' => $product->id,
            'voucher_no' => 'VCR-'.random_int(100, 999),
            'status' => 0,
        ];

        if ($product->is_box) {
            $qty = random_int(1, 10);
            $size = fake()->randomElement([5, 5.5, 6]);

            return $attributes + [
                'unit' => PackingType::Box->value,
                'qty' => $qty,
                'size' => $size,
                'total_qty' => $qty * $size,
            ];
        }

        $qty = random_int(2, 12);

        return $attributes + [
            'unit' => PackingType::Thaan->value,
            'qty' => $qty,
            'size' => 0,
            'total_qty' => $qty * fake()->randomElement([21, 25, 27]),
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RandomException
     */
    private function returnItemAttributes(Inventory $inventory): array
    {
        $rate = $inventory->cost;

        if ($inventory->unit === PackingType::Box) {
            $qty = random_int(1, max(1, intdiv($inventory->qty, 2)));

            return [
                'product_id' => $inventory->product_id,
                'unit' => PackingType::Box->value,
                'size' => $inventory->size,
                'qty' => $qty,
                'total_qty' => $qty * $inventory->size,
                'rate' => $rate,
                'total_amount' => $qty * $rate,
            ];
        }

        $meters = max(1, (int) floor($inventory->meters * fake()->randomFloat(2, 0.2, 0.5)));

        return [
            'product_id' => $inventory->product_id,
            'unit' => $inventory->unit->value,
            'size' => 0,
            'qty' => 1,
            'total_qty' => $meters,
            'rate' => $rate,
            'total_amount' => $meters * $rate,
        ];
    }

    private function getProducts(): Collection
    {
        /** @var Collection<int, Product> */
        return once(fn () => Product::query()->get());
    }

    private function getSuppliers(): Collection
    {
        return once(fn () => Account::query()->suppliers()->get());
    }
}
