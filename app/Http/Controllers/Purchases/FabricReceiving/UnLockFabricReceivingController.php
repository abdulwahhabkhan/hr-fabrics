<?php

namespace App\Http\Controllers\Purchases\FabricReceiving;

use App\Actions\Inbound\FabricReceiving\UnLockFabricReceiving;
use App\Http\Controllers\Controller;
use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Redirect;
use Throwable;

final class UnLockFabricReceivingController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(FabricReceiving $fabric_receiving, Request $request)
    {
        Gate::authorize('unlock', $fabric_receiving);
        Gate::authorize('check-inventory', $fabric_receiving);

        resolve(UnLockFabricReceiving::class)->handle($fabric_receiving, $request->user());

        return Redirect::route('purchases.fabric-receivings.edit', $fabric_receiving)
            ->with(['success' => 'Receiving unlocked successfully']);
    }
}
