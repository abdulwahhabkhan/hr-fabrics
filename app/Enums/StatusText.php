<?php

namespace App\Enums;

enum StatusText: string
{
    use HasOptions;

    case Open = 'Open';
    case Close = 'Close';
    case Cancel = 'Cancel';

}
