<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

class LogFailedLoginListener
{
    public function __construct() {}

    public function handle(Failed $event): void
    {
        /** @var User|null $user */
        $user = $event->user;
        $request = request();
        $user?->loginActivity()->create([
            'ip' => $request->ip(),
            'info' => [
                'browser' => $request->header('user-agent'),
                'client_id' => $request->getClientIp(),
                'host' => $request->getHost(),
            ],
            'status' => LoginActivity::FAILED,
        ]);

    }
}
