<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum OrderShipped: int
{
    use HasOptions;

    case Yes = 1;
    case No = 0;

}
