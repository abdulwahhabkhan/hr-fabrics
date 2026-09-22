<?php

namespace App\Models\Stock;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class Conversion extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * created by user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault();
    }

    /**
     * Filter based on request params
     *
     * @return Builder mixed
     */
    #[Scope]
    protected function filter(Builder $query, Request $params): Builder
    {
        if ($params->has('search')) {
            $query->where('sku', 'like', '%'.$params->get('search').'%');
        }

        return $query;
    }

    protected function casts(): array
    {
        return [
            'from' => AsCollection::class,
            'to' => AsCollection::class,
        ];
    }
}
