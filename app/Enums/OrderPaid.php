<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum OrderPaid: int
{
    use HasOptions;

    case UnPaid = 0;
    case Paid = 1;

}
