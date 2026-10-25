<?php

namespace App\Models\Stock;

use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Traits\BelongsToCreatedBy;
use App\Models\Traits\BelongsToProduct;
use App\Models\Traits\MorphManayToLog;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Moves stock of a single product between two stock locations.
 */
class StockTransfer extends Model implements Logable
{
    use BelongsToCreatedBy;
    use BelongsToProduct;
    use HasFactory;
    use MorphManayToLog;
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'product_id' => 'integer',
        'size' => 'float',
        'qty' => 'integer',
        'meters' => 'float',
        'unit' => PackingType::class,
        'from_location' => StockLocation::class,
        'to_location' => StockLocation::class,
        'status' => StatusText::class,
        'transaction_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    public static function nextTransferNo(): string
    {
        $sr = ((int) self::withTrashed()->max('id')) + 1;

        return 'LTR-'.date('ym').str((string) $sr)->padLeft(3, '0');
    }

    /**
     * Inventory lines this transfer took out of the source location.
     *
     * @return MorphMany<Inventory, $this>
     */
    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'outbound');
    }

    /**
     * Lots this transfer opened at the destination location.
     *
     * @return Builder<Inventory>
     */
    public function arrivedInventories(): Builder
    {
        return Inventory::query()->whereIn('parent_id', $this->inventories()->select('id'));
    }

    public function isOpen(): bool
    {
        return $this->status === StatusText::Open;
    }

    public function isClosed(): bool
    {
        return $this->status === StatusText::Close;
    }

    public function isSizedUnit(): bool
    {
        return $this->unit !== PackingType::Thaan;
    }

    #[Scope]
    protected function confirmed(Builder $query): Builder
    {
        return $query->where('status', StatusText::Close->value);
    }

    #[Scope]
    protected function atLocation(Builder $query, ?string $location): Builder
    {
        if (! $location) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($location) {
            $query->where('from_location', $location)
                ->orWhere('to_location', $location);
        });
    }
}
