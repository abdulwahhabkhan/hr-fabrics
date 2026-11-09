<?php

namespace App\Models\Stock;

use App\Enums\StockLocation;
use App\Models\Model;
use App\Models\Traits\BelongsToProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Cuts thaan stock of a product into suits or thaans of another length at one location.
 *
 * @property Collection<string, mixed> $from
 * @property Collection<string, mixed> $to
 * @property StockLocation $location
 * @property CarbonImmutable|null $transaction_date
 */
class Conversion extends Model
{
    use BelongsToProduct;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * created by user
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault();
    }

    /**
     * Thaan lines this conversion consumed.
     *
     * @return MorphMany<Inventory, $this>
     */
    public function consumedInventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'outbound');
    }

    /**
     * Lots this conversion produced, including any later split off them by a sale.
     *
     * @return MorphMany<Inventory, $this>
     */
    public function producedInventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'stockable');
    }

    public function fromMeters(): float
    {
        return round((float) $this->from['qty'] * (float) $this->from['size'], 2);
    }

    public function toMeters(): float
    {
        return round((float) $this->to['qty'] * (float) $this->to['size'], 2);
    }

    /**
     * Filter based on request params
     *
     * @return Builder mixed
     */
    #[Scope]
    protected function filter(Builder $query, Request $params): Builder
    {
        if ($params->filled('search')) {
            $query->where('sku', 'like', '%'.$params->get('search').'%');
        }

        if ($location = StockLocation::tryFrom((string) $params->input('location'))) {
            $query->where('location', $location->value);
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'from' => AsCollection::class,
            'to' => AsCollection::class,
            'location' => StockLocation::class,
            'transaction_date' => 'date',
        ];
    }
}
