<?php

namespace App\Models\Stock;

use App\Casts\FloorInteger;
use App\Enums\PaymentMode;
use App\Enums\StoreTransferStatus;
use App\Models\Contracts\Journalable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Traits\BelongsToAccount;
use App\Models\Traits\BelongsToCreatedBy;
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

/**
 * @property-read bool $is_closed
 * @property-read bool $is_opened
 */
class StoreTransfer extends Model implements Journalable, Logable
{
    use BelongsToAccount;
    use BelongsToCreatedBy;
    use HasFactory;
    use MorphManayToLog;
    use MorphToJournal;
    use SoftDeletes;

    protected $guarded = [];

    protected $appends = ['is_closed', 'is_opened'];

    protected $casts = [
        'details' => AsCollection::class,
        'total' => FloorInteger::class,
        'total_qty' => 'float',
        'status' => StoreTransferStatus::class,
        'payment_mode' => PaymentMode::class,
        'expenses' => 'integer',
        'discount_on_total' => 'integer',
        'net_total' => FloorInteger::class,
        'confirmed_at' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StoreTransferItem::class);
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'outbound');
    }

    /**
     * Store transfer record created by
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalDetail(): string
    {
        return implode(', ', [
            'Transfer No: '.$this->transfer_no,
            $this->account->name,
        ]);
    }

    #[Scope]
    public function confirmed(Builder $query): Builder
    {
        return $query->where('status', StoreTransferStatus::Closed);
    }

    protected function isClosed(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === StoreTransferStatus::Closed,
        );
    }

    protected function isOpened(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->status === StoreTransferStatus::Open,
        );
    }
}
