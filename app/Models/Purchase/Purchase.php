<?php

namespace App\Models\Purchase;

use App\Enums\StatusText;
use App\Models\Accounts\Account;
use App\Models\Catalog\Product;
use App\Models\Contracts\Journalable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Stock\Inventory;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\MorphManayToLog;
use App\Models\Traits\MorphToJournal;
use App\Models\Traits\TransactionDateScopes;
use App\Policies\Purchase\PurchasePolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string supplier_name
 */
#[UsePolicy(PurchasePolicy::class)]
final class Purchase extends Model implements Journalable, Logable
{
    use BelongsToUser;
    use HasFactory;
    use MorphManayToLog;
    use MorphToJournal;
    use TransactionDateScopes;

    public static string $module = 'PO';

    protected $guarded = [];

    protected $casts = [
        'supplier_id' => 'integer',
        'stock_id' => 'integer',
        'total_qty' => 'float',
        'status' => StatusText::class,
        'transaction_date' => 'date',
        'discount' => 'integer',
        'total' => 'integer',
        'total_return' => 'integer',
    ];

    public function stocks(): Builder
    {
        return FabricReceiving::query()
            ->whereIn('id', str($this->stock_ids)->explode(','));
    }

    public function isClosed(): bool
    {
        return $this->status === StatusText::Close;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function itemsWithProduct(): HasMany
    {
        return $this->items()
            ->select([
                PurchaseItem::qCol('*'),
                Product::qCol('name as product_name'),
            ])
            ->join(Product::tName(), 'product_id', '=', Product::qCol('id'));
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'supplier_id');
    }

    public function journalDetail(): string
    {
        return implode(', ', [
            'Bill No:' => $this->bill_no,
            'Ref No:' => $this->invoice_no,
            'Bilti No:' => $this->bilti_no,
        ]);
    }

    public function inventories(): Builder|self
    {
        return Inventory::query()
            ->where('stockable_type', FabricReceiving::morphClass())
            ->whereIn('stockable_id', explode(',', $this->stock_ids));
    }

    #[Scope]
    public function confirmed(Builder $query): Builder
    {
        return $query->where('status', StatusText::Close);
    }

    protected function transactionDisplayDate(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->transaction_date) {
                return $this->updated_at;
            }

            return $this->transaction_date;
        });
    }
}
