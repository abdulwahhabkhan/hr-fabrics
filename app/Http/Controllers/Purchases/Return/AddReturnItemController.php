<?php

namespace App\Http\Controllers\Purchases\Return;

use App\Actions\Inbound\Return\AddReturnItem;
use App\Enums\StockLocation;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Catalog\Product;
use App\Models\Purchase\PurchaseReturn;
use Closure;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $this->authorize('update', $return);
        $data = $request->validate([
            'qty' => 'required|numeric',
            'unit' => 'required|string',
            'rate' => 'required|numeric',
            'size' => 'sometimes|numeric',
            'total_qty' => 'sometimes|numeric',
            'location' => ['nullable', Rule::enum(StockLocation::class)],
            'product_id' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail) use ($return): void {
                    $isSupplied = Product::query()
                        ->suppliedBy($return->supplier_id)
                        ->whereKey($value)
                        ->exists();

                    if (! $isSupplied) {
                        $fail('The selected product is not supplied by this supplier.');
                    }
                },
            ],
        ]);
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
