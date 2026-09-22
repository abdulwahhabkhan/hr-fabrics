<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Fileable
{
    public function files(): MorphMany;
}
