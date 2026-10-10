<?php

namespace App\Enums;

use App\Traits\HasOptions;

enum EntryType: string
{
    use HasOptions;

    case Debit = 'debit';
    case Credit = 'credit';
}
