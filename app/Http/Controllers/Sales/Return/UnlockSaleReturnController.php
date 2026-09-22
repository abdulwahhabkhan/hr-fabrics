<?php

namespace App\Http\Controllers\Sales\Return;

use App\Actions\Outbound\SaleReturns\UnlockSaleReturn;
use App\Http\Controllers\Controller;
use App\Models\Sales\SalesReturn;
use App\Services\SalesService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Redirect;
use Throwable;

class UnlockSaleReturnController extends Controller
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(SalesReturn $return, Request $request)
    {
        $this->authorize('unlock', $return);
        $this->authorize('check-inventory', $return);
        DB::transaction(function () use ($return, $request) {
            resolve(UnlockSaleReturn::class)->handle($return, $request->user());
        });
        //  SalesService::unlockSalesOrderReturn($return->id, $request->user()->role_id);

        return Redirect::route('sales.returns.edit', $return->id)
            ->with(['success' => 'Sales Returns unlocked successfully']);
    }
}
