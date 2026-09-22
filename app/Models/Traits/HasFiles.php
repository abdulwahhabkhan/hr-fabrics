<?php

namespace App\Models\Traits;

use App\Models\File;
use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait HasFiles
{
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
