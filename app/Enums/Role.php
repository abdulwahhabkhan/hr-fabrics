<?php

namespace App\Enums;

enum Role: int
{
    case SuperAdmin = 10;
    case Owner = 11;
    case Manager = 12;
}
