<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Actions\Inbound\Return\AddReturnItem;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Purchase\PurchaseReturn;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class AddReturnItemController extends Controller
{
    /**
     * @throws Exception
     * @throws Throwable
     */
    public function __invoke(PurchaseReturn $return, Request $request)
    {
        $data = $request->validate([
            'qty' => 'required|numeric',
            'unit' => 'required|string',
            'rate' => 'required|numeric',
            'size' => 'sometimes|numeric',
            'total_qty' => 'sometimes|numeric',
            'product_id' => 'required|exists:products,id',
        ]);
        $this->authorize('update', $return);
        try {
            DB::transaction(function () use ($return, $data) {
                resolve(AddReturnItem::class)->handle($return, $data);
            });
        } catch (InsufficientStockException $e) {

            throw ValidationException::withMessages([
                'product' => [$e->getMessage()],
            ]);
        }

        $items = $return->itemsWithProduct()->get();

        return response()->json([
            'items' => $items,
        ]);
    }
}
