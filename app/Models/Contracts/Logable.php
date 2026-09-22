<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Logable
{
    public function logs(): MorphMany;
}
