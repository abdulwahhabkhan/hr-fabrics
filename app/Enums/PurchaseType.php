<?php

namespace App\Enums;

enum PurchaseType: string
{
    use HasOptions;

    case InPerson = 'In Person';
    case Online = 'Online';

}
