<?php

namespace App\Actions\Stock\StoreTransfers;

use App\Enums\StoreTransferStatus;
use App\Enums\StoreTransferType;
use App\Models\Stock\StoreTransfer;
use App\Models\User;

class CreateStoreTransfer
{
    public function handle(array $data, User $user): StoreTransfer
    {
        $transfer_sr = StoreTransfer::query()->max('transfer_sr');
        $transfer_sr++;
        $transfer_no = date('ym').mb_str_pad($transfer_sr, 3, '0', STR_PAD_LEFT);
        $type = $data['type'] ?? StoreTransferType::Store->value;
        if ($type === StoreTransferType::Store->value) {
            $transfer_no = 'ST-'.$transfer_no;
        } else {
            $transfer_no = 'STR-'.$transfer_no;
        }

        return StoreTransfer::create([
            'created_by' => $user->id,
            'account_id' => $data['account_id'],
            'type' => $type,
            'transfer_no' => $transfer_no,
            'transfer_sr' => $transfer_sr,
            'status' => StoreTransferStatus::Open,
        ]);
    }
}
