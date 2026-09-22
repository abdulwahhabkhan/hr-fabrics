<?php

namespace App\Models\Purchase;

use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Contracts\Journalable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Stock\Inventory;
use App\Models\Traits\MorphManayToLog;
use App\Models\Traits\MorphToJournal;
use App\Models\User;
use App\Policies\Purchase\PurchaseReturnPolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string supplier_name
 */
#[UsePolicy(PurchaseReturnPolicy::class)]
class PurchaseReturn extends Model implements Journalable, Logable
{
    use HasFactory;
    use MorphManayToLog;
    use MorphToJournal;

    protected $guarded = ['id'];

    protected $casts = [
        'info' => AsCollection::class,
        'status' => ReturnStatus::class,
        'transaction_date' => 'date',
        'total_qty' => 'float',
        'total_amount' => 'float',
        'total' => 'float',
        'expenses' => 'float',
        'discount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'supplier_id');
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'outbound');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function itemsWithProduct(): HasMany
    {
        return $this->items()
            ->select([
                PurchaseReturnItem::qCol('*'),
                Product::qCol('name as product_name'),
            ])
            ->join(Product::tName(), 'product_id', '=', Product::qCol('id'));
    }

    public function journalDetail(): string
    {
        return implode(', ', [
            'Bill No:' => $this->bill_no,
            'Ref No:' => $this->invoice_no,
            'Bilti No:' => $this->bilti_no,
        ]);
    }

    #[Scope]
    public function confirmed(Builder $query): Builder
    {
        return $query->where('status', ReturnStatus::Closed);
    }
}
