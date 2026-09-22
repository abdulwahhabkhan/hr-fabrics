<?php

namespace App\Models\Traits;

use App\Models\Action\Log;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait MorphManayToLog
{
    public function logs(): MorphMany
    {
        return $this->morphMany(Log::class, 'loggable');
    }
}
