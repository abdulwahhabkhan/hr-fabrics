<?php

namespace App\Models\Catalog;

use App\Models\Accounts\Account;
use App\Models\Model;
use App\Models\Stock\Inventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $brand_name
 * @property-read string $purchased_price
 * @property int $product_id
 * @property-read string $unit
 * @property-read float $price
 * @property-read float $qty
 * @property-read float $total_qty
 * @property-read float $total_meters
 * @property-read float|null $closing_qty
 * @property-read float|null $closing_price
 *
 * @method static Builder inRandomOrder()
 */
final class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    public static string $availableCacheKey = 'products.available.list';

    protected $guarded = ['id'];

    protected static string $cacheKey = 'products.list';

    protected $casts = [
        'is_box' => 'integer',
        'is_available' => 'boolean',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vendor_id');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        self::saved(function () {
            Cache::forget(self::$cacheKey);
            Cache::forget(self::$availableCacheKey);
            Cache::forget(self::class);
        });
    }

    #[Scope]
    protected function available(Builder $query): Builder
    {
        $subQuery = Inventory::query()
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('SUM(meters) > 0');

        return $query->joinSub($subQuery, 'inventory', function ($join) {
            $join->on('inventory.product_id', '=', Product::qCol('id'));
        });
    }
}
