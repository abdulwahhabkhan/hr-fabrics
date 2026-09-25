<?php

namespace App\Models\Purchase;

use App\Models\Catalog\Product;
use App\Models\Model;
use App\Models\Stock\Inventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $product_name
 * @property-read float $total_meters
 */
final class FabricReceivingItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'product' => 'integer',
        'product_id' => 'integer',
        'fabric_receiving_id' => 'integer',
        'total_qty' => 'float',
        'size' => 'float',
    ];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<FabricReceiving, $this>
     */
    public function fabricReceiving(): BelongsTo
    {
        return $this->belongsTo(FabricReceiving::class);
    }

    /**
     * @return HasMany<Inventory, $this>
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'stockable_item_id', 'id')
            ->withAttributes(['stockable_type' => FabricReceiving::morphClass()]);
    }
}
