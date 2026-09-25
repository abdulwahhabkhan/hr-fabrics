<?php

namespace App\Models\Accounts;

use App\Models\Model;
use App\Models\Traits\BelongsToAccount;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $total_dr
 * @property-read int $total_cr
 * @property-read int $total_debit
 * @property-read int $total_credit
 * @property-read int $balance
 */
class JournalDetail extends Model
{
    use BelongsToAccount;
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dr' => 'integer',
        'cr' => 'integer',
        'account_id' => 'int',
    ];

    /**
     * @return BelongsTo<Journal, $this>
     */
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function getBalance(int $account_id): int
    {
        $balance = self::query()
            ->selectRaw('sum(dr) as total_dr')
            ->selectRaw('sum(cr) as total_cr')
            ->where('account_id', $account_id)
            ->first();

        return (int) $balance->total_dr - (int) $balance->total_cr;
    }

    protected function totalBalance(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->total_debit - $this->total_credit),

        );
    }

    #[Scope]
    protected function overDue(Builder $query, int $accountId, int $total): Builder
    {
        $sumQuery = self::query()
            ->select(['journal_id', 'created_at'])
            ->selectRaw('sum(dr) over (order by journal_id) debit')
            ->where('account_id', $accountId)
            ->where('dr', '>', 0)
            ->orderBy('journal_id');

        return $query
            ->select('*')
            ->selectRaw('(debit - '.$total.') as balance')
            ->fromSub($sumQuery->toRawSql(), 'ledger')
            ->where('debit', '>', $total);
    }
}
