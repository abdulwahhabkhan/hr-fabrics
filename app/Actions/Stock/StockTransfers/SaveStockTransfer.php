<?php

namespace App\Actions\Stock\StockTransfers;

use App\Actions\Inventory\CheckStockAvailability;
use App\Enums\PackingType;
use App\Enums\StatusText;
use App\Enums\StockLocation;
use App\Models\Stock\StockTransfer;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SaveStockTransfer
{
    public function __construct(private CheckStockAvailability $checkStockAvailability) {}

    /**
     * Create a draft transfer or update an open one. Stock is not moved until the transfer is confirmed.
     *
     * @param  array{product_id: int, unit: string, size?: float|string|null, qty: int|string, meters?: float|string|null, from_location: string, to_location: string, notes?: string|null}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, User $user, ?StockTransfer $transfer = null): StockTransfer
    {
        $unit = PackingType::from($data['unit']);
        $qty = (int) $data['qty'];
        $size = $unit === PackingType::Thaan ? 0.0 : (float) $data['size'];
        $meters = $unit === PackingType::Thaan ? (float) $data['meters'] : $qty * $size;

        $this->checkStockAvailability->handle(
            (int) $data['product_id'],
            $unit,
            StockLocation::from($data['from_location']),
            $qty,
            $meters,
            $size,
        );

        $attributes = [
            'product_id' => (int) $data['product_id'],
            'unit' => $unit,
            'size' => $size,
            'qty' => $qty,
            'meters' => $meters,
            'from_location' => $data['from_location'],
            'to_location' => $data['to_location'],
            'notes' => $data['notes'] ?? null,
        ];

        if ($transfer) {
            $transfer->update($attributes);

            return $transfer;
        }

        return StockTransfer::query()->create([
            ...$attributes,
            'transfer_no' => StockTransfer::nextTransferNo(),
            'status' => StatusText::Open,
            'created_by' => $user->id,
        ]);
    }
}
