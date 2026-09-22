<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoginActivity extends Model
{
    use BelongsToUser;
    use HasFactory;

    public const LOGIN = '2';

    public const FAILED = '3';

    public const AUTHENTICATE = '4';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'info' => AsCollection::class,
        ];
    }
}
