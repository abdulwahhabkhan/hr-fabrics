<?php

namespace App\Actions\Stock\Conversions;

use App\Models\Stock\Conversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeleteConversion
{
    /**
     * Reverse a conversion: remove the produced lots and release the consumed thaan lines back to stock.
     * Refused once any of the produced stock has been sold, returned or transferred.
     *
     * @throws Throwable
     */
    public function handle(Conversion $conversion): void
    {
        DB::transaction(function () use ($conversion) {
            $produced = $conversion->producedInventories()->lockForUpdate()->get();

            if ($produced->contains(fn ($lot) => $lot->outbound_id !== null)) {
                throw ValidationException::withMessages([
                    'conversion' => 'Stock produced by this conversion has already been used, it cannot be deleted.',
                ]);
            }

            $conversion->producedInventories()->delete();
            $conversion->consumedInventories()->update([
                'outbound_type' => null,
                'outbound_id' => null,
                'outbound_item_id' => null,
                'outbound_on' => null,
            ]);

            $conversion->delete();
        });
    }
}
