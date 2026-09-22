<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public static function getAll(): Collection|array
    {
        return self::query()->select(['name'])->orderBy('name')->get();
    }

    #[Scope]
    protected function filter(Builder $query, $params): Builder
    {
        if ($params->has('search')) {
            $query->where('name', 'like', '%'.$params->get('search').'%');
        }

        return $query;
    }
}
