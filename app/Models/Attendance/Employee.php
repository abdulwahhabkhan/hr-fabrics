<?php

namespace App\Models\Attendance;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];

    protected $keyType = 'string';

    protected $casts = [
        'info' => 'array',
    ];

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'worker_id');
    }

    /**
     * @param  array<string, mixed>  $request
     */
    #[Scope]
    protected function filter(Builder $query, array $request): void
    {
        $query->when($request['search'] ?? null,
            fn (Builder $q, string $v): Builder => $q->where('name', 'like', "%{$v}%"));
    }
}
