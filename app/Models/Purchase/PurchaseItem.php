<?php

namespace App\Models\Purchase;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $product_name
 */
final class PurchaseItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'product_id' => 'integer',
        'purchase_id' => 'integer',
        'unit' => PackingType::class,
        'qty' => 'integer',
        'size' => 'float',
        'total_qty' => 'float',
        'price' => 'float',
        'total' => 'float',
    ];

    /**
     * @return BelongsTo<Purchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Order Item products
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isBox(): bool
    {
        return $this->unit === PackingType::Box;
    }

    public function isThaan(): bool
    {
        return $this->unit === PackingType::Thaan;
    }

    public function isSuit(): bool
    {
        return $this->unit === PackingType::Suit;
    }
}
