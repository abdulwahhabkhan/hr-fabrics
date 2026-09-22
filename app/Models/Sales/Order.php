<?php

namespace App\Models\Sales;

use App\Casts\FloorInteger;
use App\Enums\DiscountType;
use App\Enums\OrderPaid;
use App\Enums\OrderStatus;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Contracts\Fileable;
use App\Models\Contracts\Journalable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Stock\Inventory;
use App\Models\Traits\BelongsToCustomer;
use App\Models\Traits\HasFiles;
use App\Models\Traits\MorphManayToLog;
use App\Models\Traits\MorphToJournal;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model implements Fileable, Journalable, Logable
{
    use BelongsToCustomer;
    use HasFactory;
    use HasFiles;
    use MorphManayToLog;
    use MorphToJournal;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'agent_rate' => AsCollection::class,
        'details' => AsCollection::class,
        'total' => FloorInteger::class,
        'total_qty' => 'float',
        'net_total' => FloorInteger::class,
        'discount' => 'integer',
        'commission' => 'integer',
        'customer_discount' => 'float',
        'confirmed_at' => 'date',
        'total_cost' => 'float',
        'discount_rate' => 'float',
        'discount_type' => DiscountType::class,
        'paid' => OrderPaid::class,
        'payment_mode' => 'string',
        'shipped' => 'integer',
        'status' => OrderStatus::class,
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'outbound');
    }

    public function itemsWithProduct(): HasMany
    {
        return $this->items()
            ->select([
                OrderItem::qCol('*'),
                Product::qCol('name as product_name'),
            ])
            ->join(Product::tName(), OrderItem::qCol('product_id'), '=',
                Product::qCol('id'));
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'agent_id');
    }

    public function isDiscountPerMeter(): bool
    {
        return $this->discount_type === DiscountType::FixedPerMeter;
    }

    public function journalDetail(): string
    {
        return implode(', ', [
            'Inv No: '.$this->invoice_no,
            $this->customer->name,
        ]);
    }

    public function isClosed(): bool
    {
        return $this->status === OrderStatus::Close;
    }

    #[Scope]
    public function confirmed(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Close);
    }

    protected function getNetBalanceAttribute()
    {
        return $this->net_total + $this->balance;
    }

    protected function transactionDisplayDate(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->transaction_date) {
                return $this->created_at;
            }

            return $this->transaction_date;
        });
    }

    protected function hasBilti(): Attribute
    {
        return Attribute::get(fn () => $this->files()->orderBilti()->exists());
    }

    protected function fromShop(): Attribute
    {
        return Attribute::get(fn () => str($this->purchase_type)->lower()->exactly('online'));
    }

    protected function discountLabel(): Attribute
    {
        return Attribute::get(fn () => $this->discount_type?->valueLabelShort($this->discount_rate));
    }
}
