<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Log;

trait WithCachedPermissions
{
    protected function setupWithCachedPermissions(): void
    {
        Log::debug('Setting up cached permissions');
    }
}
