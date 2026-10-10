<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum PaymentMode: string
{
    use HasOptions;

    case Cash = 'Cash';
    case Credit = 'Credit';

}
