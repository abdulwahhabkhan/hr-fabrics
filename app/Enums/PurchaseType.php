<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum PurchaseType: string
{
    use HasOptions;

    case InPerson = 'In Person';
    case Online = 'Online';

}
