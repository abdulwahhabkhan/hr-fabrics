<?php

namespace App\Models\Accounts;

use App\Models\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ledger extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'detail' => AsCollection::class,
    ];
}
