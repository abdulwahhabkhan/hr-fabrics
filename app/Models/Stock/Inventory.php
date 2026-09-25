<?php

namespace App\Models\Stock;

use App\Enums\PackingType;
use App\Models\Model;
use App\Models\Purchase\FabricReceiving;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Sales\Order;
use App\Models\Sales\SalesReturn;
use App\Models\Traits\BelongsToAccount;
use App\Models\Traits\BelongsToProduct;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read float|null $stock_value
 * @property-read float|null $total_qty
 * @property-read float|null $total_meters
 */
final class Inventory extends Model
{
    use BelongsToAccount;
    use BelongsToProduct;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'size' => 'float',
        'qty' => 'integer',
        'meters' => 'float',
        'cost' => 'float',
        'outbound_on' => 'datetime',
        'transaction_date' => 'datetime',
        'info' => AsCollection::class,
        'unit' => PackingType::class,
    ];

    /** @deprecated */
    public static function deleteTransaction(int|string $refNo, string $type): void
    {
        self::query()->where('ref_no', $refNo)->where('type', $type)->delete();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function outbound(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<FabricReceiving, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(FabricReceiving::class, 'stockable_id', 'id')
            ->withAttributes([
                self::qCol('stockable_type', false) => FabricReceiving::morphClass(),
            ]);
    }

    /**
     * @return BelongsTo<PurchaseReturn, $this>
     */
    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class, 'outbound_id', 'id')
            ->withAttributes([
                self::qCol('outbound_type', false) => PurchaseReturn::morphClass(),
            ]);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'outbound_id', 'id')
            ->withAttributes([
                self::qCol('outbound_type', false) => Order::morphClass(),
            ]);
    }

    /**
     * @return BelongsTo<SalesReturn, $this>
     */
    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class, 'stockable_id', 'id')
            ->withAttributes([
                self::qCol('stockable_type', false) => SalesReturn::morphClass(),
            ]);
    }

    #[Scope]
    protected function available(Builder $query): Builder
    {
        return $query->whereNull('outbound_id')
            ->whereNotNull('transaction_date')
            ->where('meters', '>', 0);
    }

    #[Scope]
    protected function stockValue(Builder $query, bool $total = false): Builder
    {
        $inventory_unit = self::qCol('unit', false);

        $expression = 'CASE WHEN '.$inventory_unit.' = "'.PackingType::Box->value.'" THEN  qty * cost ELSE meters * cost END';
        if ($total) {
            return $query->selectRaw('SUM('.$expression.') as stock_value');
        }

        return $query->selectRaw($expression);
    }

    /**
     * @param  Builder<Inventory>  $query
     * @return Builder<Inventory>
     */
    #[Scope]
    protected function availableBefore(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where(function (Builder $query) use ($date) {
                $query
                    ->whereNull('outbound_id')
                    ->orWhere('outbound_on', '>=', $date->toDateString());
            })
            ->available();
    }

    /**
     * @param  Builder<Inventory>  $query
     * @return Builder<Inventory>
     */
    #[Scope]
    protected function availableAfter(Builder $query, CarbonInterface $date): Builder
    {
        return $query
            ->where(function (Builder $query) use ($date) {
                $query
                    ->whereNull('outbound_id')
                    ->orWhere('outbound_on', '>', $date->toDateString());
            })
            ->available();
    }

    #[Scope]
    protected function booked(Builder $query): Builder
    {
        return $query->whereNotNull('outbound_id');
    }

    #[Scope]
    protected function fifo(Builder $query): Builder
    {
        $query->oldest('transaction_date');

        return $query->orderBy('id');
    }
}
