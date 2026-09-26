<?php

namespace App\Models\Accounts;

use App\Enums\AccountType;
use App\Enums\DiscountType;
use App\Models\Contracts\Fileable;
use App\Models\Model;
use App\Models\Traits\HasFiles;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property-read int $supplier_id
 * @property-read string $supplier_name
 *
 * @method static ReceiptAccounts()
 */
class Account extends Model implements Fileable
{
    use HasFactory;
    use HasFiles;
    use SoftDeletes;

    public static array $partnersIds = [734];

    protected $guarded = ['id'];

    protected $casts = [
        'type' => AccountType::class,
        'address' => AsCollection::class,
        'commission_rate' => AsCollection::class,
        'discount' => 'float',
        'limit' => 'float',
        'expense_account' => 'integer',
        'credit' => 'boolean',
        'balance_date' => 'date:Y-m-d',
        'suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'discount_type' => DiscountType::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault();
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'agent_id')
            ->withDefault();
    }

    /**
     * @return HasMany<Ledger, $this>
     */
    public function ledger(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    #[Scope]
    protected function partners(Builder $query): Builder
    {
        $query->where(fn (Builder $where) => $where->where('type', AccountType::Partner)
            ->orWhereIn('id', $this->partnerIds()));

        return $query;
    }

    #[Scope]
    protected function agents(Builder $query): Builder
    {
        $query->where('type', '=', AccountType::Agent);

        return $query;
    }

    #[Scope]
    protected function accounts(Builder $query): Builder
    {
        $query->whereNotIn('type', [AccountType::Supplier->value, AccountType::Customer->value]);

        return $query;
    }

    #[Scope]
    protected function expenses(Builder $query): Builder
    {
        $query->where('type', AccountType::Expenses);

        return $query;
    }

    #[Scope]
    protected function customers(Builder $query): Builder
    {
        $query->where($this->qualifyColumn('type'), '=', AccountType::Customer);

        return $query;
    }

    #[Scope]
    protected function material(Builder $query): Builder
    {
        $query->where('type', '=', AccountType::Material);

        return $query;
    }

    #[Scope]
    protected function typeStore(Builder $query): Builder
    {
        return $query->where('type', AccountType::Store);
    }

    #[Scope]
    protected function paymentAccounts(Builder $query): Builder
    {
        $query->select(['id', 'name', 'type']);
        $query->whereIn('type',
            [AccountType::Supplier->value, AccountType::Agent->value, AccountType::Material->value]);

        return $query;
    }

    #[Scope]
    protected function receiptAccounts(Builder $query): Builder
    {
        $query->select(['id', 'name', 'type']);
        $query->whereIn('type', [AccountType::Customer->value]);

        return $query;
    }

    #[Scope]
    protected function suppliers(Builder $query): Builder
    {
        $query->where('type', '=', AccountType::Supplier);

        return $query;
    }

    #[Scope]
    protected function employees(Builder $query): Builder
    {
        $query->where('type', '=', AccountType::Employee);

        return $query;
    }

    #[Scope]
    protected function withBalance(Builder $query): Builder
    {
        return $query->where('balance', '>', 0);
    }

    #[Scope]
    protected function selectAC(Builder $query): Builder
    {
        return $query->select([
            'id',
            'name',
        ])->orderBy('name', 'asc');
    }

    #[Scope]
    protected function selectForSales(Builder $query): Builder
    {
        return $query->select([
            'id',
            'id as customer_id',
            'name as customer_name',
            'address->city as city',
            'discount',
            'discount_type',
            'suspended',
            'agent_id',
        ]);
    }

    #[Scope]
    protected function selectForStoreTransfer(Builder $query): Builder
    {
        return $query->select([
            'id',
            'id as account_id',
            'name as account_name',
            'address->city as city',
        ]);
    }

    #[Scope]
    protected function orderByName(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    #[Scope]
    protected function fullName(): Attribute
    {
        return Attribute::make(get: fn ($value, $attributes) => $attributes['name'].', '.$attributes['address']['city']);
    }

    private function partnerIds(): array
    {
        return config('store.partners_ids');
    }
}
