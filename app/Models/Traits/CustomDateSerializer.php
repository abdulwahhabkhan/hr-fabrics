<?php

namespace App\Models\Traits;

use DateTimeInterface;

trait CustomDateSerializer
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s');
    }
}
