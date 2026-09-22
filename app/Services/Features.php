<?php

namespace App\Services;

class Features
{
    public static function enabled(string $feature): bool
    {
        return in_array($feature, config('fortify.features', []));
    }
}
