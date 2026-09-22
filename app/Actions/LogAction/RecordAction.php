<?php

namespace App\Actions\LogAction;

use App\Models\Action\Log;
use App\Models\Model;
use App\Models\User;
use InvalidArgumentException;

final class RecordAction
{
    public function handle(Model $model, User $user, ?string $action = null, ?array $log = null): Log
    {
        if (! $action && ! $log) {
            throw new InvalidArgumentException('Action or Log is required');
        }
        $log ??= [];
        if ($action) {
            $log['action'] = $action;
        }

        $log['user'] = [
            'id' => $user->id,
            'name' => $user->name,
        ];

        return $model->logs()
            ->create(['log' => $log]);
    }
}
