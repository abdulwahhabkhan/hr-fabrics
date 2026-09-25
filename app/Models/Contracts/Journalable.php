<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Journalable
{
    public function journal(): MorphOne;

    public function journalDetail(): string;
}
