<?php

namespace App\Models\Purchase;

use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Contracts\Fileable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Sales\Order;
use App\Models\Stock\Inventory;
use App\Models\Traits\HasFiles;
use App\Models\Traits\MorphManayToLog;
use App\Models\Traits\TransactionDateScopes;
use App\Models\User;
use App\Policies\Purchase\FabricReceivingPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read string|null $supplier_name
 */
#[UsePolicy(FabricReceivingPolicy::class)]
class FabricReceiving extends Model implements Fileable, Logable
{
    use HasFactory;
    use HasFiles;
    use MorphManayToLog;
    use SoftDeletes;
    use TransactionDateScopes;

    protected $guarded = ['id'];

    protected $casts = [
        'info' => AsCollection::class,
        'photos' => AsCollection::class,
        'invoiced' => 'bool',
        'transaction_date' => 'date:Y-m-d',
        'status' => StatusText::class,
        'total_qty' => 'float',
        'total_meters' => 'float',
    ];

    public function isClosed(): bool
    {
        return $this->status === StatusText::Close;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'supplier_id');
    }

    /**
     * @return HasMany<FabricReceivingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(FabricReceivingItem::class);
    }

    /**
     * @return MorphMany<Inventory, $this>
     */
    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'stockable');
    }

    /**
     * Purchase vouchers this receiving was invoiced on. A voucher keeps its receivings as a comma separated id list.
     *
     * @return Builder<Purchase>
     */
    public function purchases(): Builder
    {
        return Purchase::query()->whereRaw('FIND_IN_SET(?, stock_ids)', [$this->id]);
    }

    /**
     * Sale orders that were issued stock from this receiving.
     *
     * @return Builder<Order>
     */
    public function saleOrders(): Builder
    {
        return Order::query()->whereIn(Order::qCol('id'), $this->inventories()
            ->where('outbound_type', Order::morphClass())
            ->select('outbound_id'));
    }

    /**
     * @return HasMany<FabricReceivingItem, $this>
     */
    public function itemsWithProduct(): HasMany
    {
        return $this->items()
            ->select([
                FabricReceivingItem::qCol('*'),
                Product::qCol('name as product_name'),
            ])
            ->join(Product::tName(), 'product_id', '=', Product::qCol('id'));
    }

    public function isOpen(): bool
    {
        return $this->status === StatusText::Open;
    }

    #[Scope]
    protected function confirmed(Builder $query): Builder
    {
        return $query->where('status', 'close');
    }

    #[Scope]
    protected function invoiced(Builder $builder, $invoiced = true): void
    {
        $builder->where('invoiced', $invoiced);
    }

    /**
     * @return Attribute<CarbonImmutable|null, never>
     */
    protected function transactionDisplayDate(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->transaction_date) {
                return $this->updated_at;
            }

            return $this->transaction_date;
        });
    }

    protected function attachments(): Attribute
    {
        return Attribute::get(fn () => $this->info['files'] ?? []);
    }
}
