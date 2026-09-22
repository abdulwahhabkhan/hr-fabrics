<?php

namespace App\Enums;

enum OrderShipped: int
{
    use HasOptions;

    case Yes = 1;
    case No = 0;

}
