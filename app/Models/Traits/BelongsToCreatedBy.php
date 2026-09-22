<?php

namespace App\Models\Traits;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @mixin Model */
trait BelongsToCreatedBy
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
