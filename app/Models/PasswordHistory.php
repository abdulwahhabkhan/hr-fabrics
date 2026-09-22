<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordHistory extends Model
{
    use BelongsToUser;
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected $hidden = ['password'];
}
