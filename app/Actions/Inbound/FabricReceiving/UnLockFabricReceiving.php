<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use App\Models\User;
use DB;
use Throwable;

final class UnLockFabricReceiving
{
    /**
     * @throws Throwable
     */
    public function handle(FabricReceiving $stock, User $user): void
    {
        resolve(RecordAction::class)->handle($stock, $user, 'Stock UnLocked');

        DB::transaction(function () use ($stock): void {
            $stock->inventories()->delete();
            $stock->update(['status' => StatusText::Open]);
        });

    }
}
