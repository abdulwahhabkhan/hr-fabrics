<?php

namespace App\Models\Sales;

use App\Casts\CeilInteger;
use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use HasFactory;

    protected $casts = [
        'unit' => PackingType::class,
        'total_commission' => CeilInteger::class,
        'size' => 'float',
        'qty' => 'float',
        'total' => 'float',
        'rate' => 'float',
        'total_amount' => 'float',
        'total_cost' => 'float',
    ];

    /**
     * @return BelongsTo<SalesReturn, $this>
     */
    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
