<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Inbound\FabricReceiving\MasterReopenFabricReceiving;
use App\Http\Controllers\Controller;
use App\Http\Resources\Settings\MasterFabricReceivingResource;
use App\Models\Accounts\Account;
use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Master user tool to reopen confirmed fabric receivings that are already invoiced or sold.
 */
final class MasterFabricReceivingController extends Controller
{
    /**
     * Look up one receiving by its reference number. Nothing is listed until a reference is searched.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('masterTools', FabricReceiving::class);
        $refNo = mb_trim((string) $request->string('ref_no'));

        $receiving = $refNo === '' ? null : FabricReceiving::query()
            ->select(FabricReceiving::qCol('*'), Account::qCol('name as supplier_name'))
            ->join(Account::tName(), 'supplier_id', '=', Account::qCol('id'))
            ->where(FabricReceiving::qCol('invoice_no'), $refNo)
            ->first();

        return Inertia::render('settings/Master/FabricReceivingReopenIndex', [
            'ref_no' => $refNo,
            'receiving' => $receiving ? MasterFabricReceivingResource::make($receiving)->resolve($request) : null,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function reopen(FabricReceiving $fabric_receiving, Request $request): RedirectResponse
    {
        Gate::authorize('masterReopen', $fabric_receiving);

        resolve(MasterReopenFabricReceiving::class)->handle($fabric_receiving, $request->user());

        return Redirect::route('purchases.fabric-receivings.edit', $fabric_receiving)
            ->with(['success' => 'Receiving and its vouchers reopened. Confirm the receiving, then the voucher.']);
    }
}
