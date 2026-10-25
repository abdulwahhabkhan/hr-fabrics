<?php

namespace App\Http\Controllers\Purchases\Purchase;

use App\Actions\Inbound\Purchase\UnLockPurchase;
use App\Http\Controllers\Controller;
use App\Models\Purchase\Purchase;
use Illuminate\Http\Request;
use Redirect;
use Throwable;

class UnlockPurchaseController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(Purchase $receipt, Request $request)
    {
        $user = $request->user();
        $this->authorize('unlock', $receipt);
        $this->authorize('check-inventory', $receipt);
        resolve(UnLockPurchase::class)->handle($receipt, $user);

        return Redirect::route('purchases.pos.edit', $receipt->id)
            ->with(['success' => 'Receiving unlocked successfully']);
    }
}
