<?php

namespace App\Http\Controllers\Purchases\Purchase;

use App\Actions\LogAction\RecordAction;
use App\Enums\StatusText;
use App\Http\Controllers\Controller;
use App\Models\Purchase\Purchase;
use DB;
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
        resolve(RecordAction::class)->handle($receipt, $user, 'Purchase UnLocked');

        DB::transaction(function () use ($receipt): void {
            $receipt->update(['status' => StatusText::Open]);
            $receipt->stocks()->update(['invoiced' => false]);
            $journals = $receipt->journal()->get();
            foreach ($journals as $journal) {
                $journal->transactions()->delete();
            }
            $receipt->journal()->delete();
        });

        return Redirect::route('purchases.pos.edit', $receipt->id)
            ->with(['success' => 'Receiving unlocked successfully']);
    }
}
