<?php

namespace App\Enums;

enum OrderPaid: int
{
    use HasOptions;

    case UnPaid = 0;
    case Paid = 1;

}
