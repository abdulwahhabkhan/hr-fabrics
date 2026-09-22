<?php

namespace App\Models\Stock;

use App\Models\Accounts\Account;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValueAddition extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'vendor_id');
    }

    public function packedBy(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'packed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    #[Scope]
    protected function dataList($query, $request)
    {
        $query->select(
            $this->qualifyColumn('*'),
            Account::qCol('name as vendor_name')
        );
        $query->join(Account::tName(), 'vendor_id', '=', Account::qCol('id'));

        $query->when($request['lot_no'] ?? null, function ($query, $search) {
            $query->where('lot_no', 'like', '%'.$search.'%');
        });

        $query->when($request['vendor_name'] ?? null, function ($query, $search) {
            $query->where('name', 'like', '%'.$search.'%');
        });

        $query->orderBy($this->qualifyColumn('updated_at'), 'desc');
    }

    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'packing_cost' => 'integer',
            'cut_piece_cost' => 'integer',
            'total_value' => 'integer',
        ];
    }
}
