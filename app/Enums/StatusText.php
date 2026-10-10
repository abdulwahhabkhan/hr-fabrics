<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum StatusText: string
{
    use HasOptions;

    case Open = 'Open';
    case Close = 'Close';
    case Cancel = 'Cancel';

}
