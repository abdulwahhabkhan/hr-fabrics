<?php

namespace App\Enums;

enum PaymentMode: string
{
    use HasOptions;

    case Cash = 'Cash';
    case Credit = 'Credit';

}
