<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Actions\Inbound\Return\UnlockReturn;
use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class UnlockReturnController extends Controller
{
    /**
     * @throws Throwable
     */
    public function __invoke(PurchaseReturn $return, Request $request): RedirectResponse
    {
        DB::transaction(function () use ($return, $request) {
            $return->status = ReturnStatus::Open;
            $return->save();
            resolve(UnlockReturn::class)->handle($return, $request->user());

        });

        return Redirect::route('purchases.por.index')
            ->with(['success' => 'PO Return Unlocked successfully']);
    }
}
