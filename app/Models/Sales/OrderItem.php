<?php

namespace App\Models\Sales;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Model;
use App\Models\Stock\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read float $total
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'discount' => 'integer',
        'qty' => 'integer',
        'cost' => 'float',
        'total_cost' => 'float',
        'total_qty' => 'float',
        'total_amount' => 'integer',
        'total_commission' => 'float',
        'commission' => 'float',
        'price' => 'float',
        'unit' => PackingType::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'outbound_item_id')
            ->withAttributes(['outbound_type' => Order::morphClass()]);
    }
}
