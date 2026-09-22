<?php

namespace App\Models\Action;

use App\Enums\Module;
use App\Models\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class Log extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'log' => AsCollection::class,
        'module' => Module::class,
    ];
}
