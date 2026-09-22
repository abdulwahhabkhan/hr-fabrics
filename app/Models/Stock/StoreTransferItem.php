<?php

namespace App\Models\Stock;

use App\Enums\PackingType;
use App\Models\Catalog\Product;
use App\Models\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class StoreTransferItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['cost_price'];

    protected $casts = [
        'store_transfer_id' => 'integer',
        'product_id' => 'integer',
        'qty' => 'float',
        'total_qty' => 'float',
        'total_amount' => 'float',
        'price' => 'float',
        'expense' => 'float',
        'unit' => PackingType::class,
    ];

    public static function getItems($id): Collection
    {
        return self::query()
            ->select([
                self::qCol('*'),
                Product::qCol('name as product_name'),
            ])
            ->join(Product::tName(), self::qCol('product_id'), '=',
                Product::qCol('id'))
            ->where('store_transfer_id', '=', $id)->get();
    }

    /**
     * Store transfer item product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Parent store transfer
     */
    public function storeTransfer(): BelongsTo
    {
        return $this->belongsTo(StoreTransfer::class);
    }

    protected function costPrice(): Attribute
    {
        return Attribute::get(fn () => $this->expense + $this->price);
    }
}
