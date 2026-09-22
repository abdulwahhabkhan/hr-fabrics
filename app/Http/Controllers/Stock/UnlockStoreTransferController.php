<?php

namespace App\Http\Controllers\Stock;

use App\Http\Controllers\Controller;
use App\Models\Stock\StoreTransfer;
use App\Services\StoreTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Redirect;

class UnlockStoreTransferController extends Controller
{
    public function __invoke(StoreTransfer $storeTransfer, Request $request, StoreTransferService $service)
    {
        Gate::authorize('unlock', $storeTransfer);
        $service->unlockStoreTransfer($storeTransfer, $request->user());

        return Redirect::route('stocks.store-transfers.edit', $storeTransfer->id)
            ->with(['success' => 'Store Transfer unlocked successfully']);
    }
}
