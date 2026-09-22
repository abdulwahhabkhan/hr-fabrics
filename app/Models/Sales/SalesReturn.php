<?php

namespace App\Models\Sales;

use App\Casts\CeilInteger;
use App\Enums\ReturnStatus;
use App\Models\Accounts\Account;
use App\Models\Contracts\Journalable;
use App\Models\Contracts\Logable;
use App\Models\Model;
use App\Models\Stock\Inventory;
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

/**
 * @property string $customer_name
 */
class SalesReturn extends Model implements Journalable, Logable
{
    use HasFactory;
    use MorphManayToLog;
    use MorphToJournal;

    protected $guarded = ['id'];

    protected $casts = [
        'total_amount' => CeilInteger::class,
        'commission' => CeilInteger::class,
        'info' => AsCollection::class,
        'agent_rate' => AsCollection::class,
        'status' => ReturnStatus::class,
        'transaction_date' => 'date',
        'total_qty' => 'float',
        'amount' => 'integer',
        'discount' => 'integer',
        'expenses' => 'integer',
        'balance' => 'integer',

    ];

    /**
     * Get totals of POR
     */
    public static function getTotal($items): array
    {
        $total_qty = collect($items)->sum('total_qty');
        $total_commission = collect($items)->sum('total_commission');
        $total_amount = collect($items)->sum('total_amount');

        return [
            'total_qty' => $total_qty,
            'total_amount' => $total_amount,
            'total_commission' => $total_commission,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inventories(): MorphMany
    {
        return $this->morphMany(Inventory::class, 'stockable');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function journalDetail(): string
    {
        return implode(', ', [
            'Ref No: '.$this->invoice_no,
            'Order No: '.$this->order_no,
        ]);
    }

    public function isClosed(): bool
    {
        return $this->status === ReturnStatus::Closed;
    }

    #[Scope]
    public function confirmed(Builder $query): Builder
    {
        return $query->where('status', ReturnStatus::Closed);
    }

    #[Scope]
    protected function sORList(Builder $query, array $request)
    {
        $query->select($this->qualifyColumn('*'), Account::qCol('name as customer_name'));
        $query->join(Account::tName(), 'customer_id', '=', Account::qCol('id'));

        $query->when($request['order_no'] ?? null, function ($query, $search) {
            $query->where('order_no', 'like', '%'.$search.'%');
        });
        $query->when($request['ref_no'] ?? null, function ($query, $search) {
            $query->where('ref_no', 'like', '%'.$search.'%');
        });
        $query->when($request['customer_name'] ?? null, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        });

        $query->orderBy($this->qualifyColumn('updated_at'), 'desc');
    }

    protected function netBalance(): Attribute
    {
        return Attribute::get(fn () => $this->balance - $this->total_amount);
    }
}
