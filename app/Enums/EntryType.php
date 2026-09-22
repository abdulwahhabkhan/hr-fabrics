<?php

namespace App\Enums;

enum EntryType: string
{
    use HasOptions;

    case Debit = 'debit';
    case Credit = 'credit';
}
