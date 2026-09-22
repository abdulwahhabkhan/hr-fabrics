<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Widget extends Model
{
    use HasFactory;

    public const string DASHBOARD_GROUP = 'Dashboard';

    public const string WIDGET_SALE = 'Sales';

    protected $guarded = [];

    protected $casts = [
        'data' => AsCollection::class,
    ];

    #[Scope]
    protected function groupDashboard(Builder $query): Builder
    {
        return $query->where('group', self::DASHBOARD_GROUP);
    }

    #[Scope]
    protected function salesWidget(Builder $query): Builder
    {
        return $query->where('name', self::WIDGET_SALE);
    }
}
