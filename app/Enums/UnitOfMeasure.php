<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum UnitOfMeasure
{
    use HasOptions;

    case Meter;
}
