<?php

namespace App\Actions\Outbound\SaleReturns;

use App\Actions\LogAction\RecordAction;
use App\Enums\Module;
use App\Enums\TransactionType;
use App\Models\Sales\SalesReturn;
use App\Models\Stock\Inventory;
use App\Models\User;
use App\Services\AccountService;
use Log;
use Throwable;

class ConfirmSaleReturn
{
    /**
     * @throws Throwable
     */
    public function handle(SalesReturn $return, User $user): void
    {
        if (! $return->isClosed()) {
            Log::warning('Confirm sale return for open return '.$return->id);

            return;
        }

        Log::info('Confirm sale return for '.$return->id.' status '.$return->status->value);
        resolve(RecordAction::class)->handle($return, $user, 'Sale Return confirmed by user');
        $accountService = resolve(AccountService::class);
        $balance = $accountService->getAccountBalance($return->customer_id);
        $return->update(['balance' => $balance]);
        resolve(ProcessSaleReturnLedger::class)->handle($return, $user);
        $this->issueInventory($return);
    }

    private function issueInventory(SalesReturn $return): void
    {
        $items = $return->returnItems;
        $return->inventories()->delete();
        $inventories = [];
        $info = [
            'inbound' => [
                'action' => 'add',
                'invoice_no' => $return->invoice_no,
                'type' => TransactionType::SaleOrderReturn->value,
                'module' => Module::SaleOrderReturn->value,
            ],
        ];
        foreach ($items as $item) {
            $inventory = new Inventory();
            $inventory->product_id = $item->product_id;
            $inventory->stockable_item_id = $item->id;
            $inventory->unit = $item->unit;
            $inventory->size = $item->size;
            $inventory->meters = $item->total_qty;
            $inventory->qty = $item->qty;
            $inventory->info = $info;
            $inventory->transaction_date = $return->transaction_date;
            $inventories[] = $inventory;

        }
        $return->inventories()->saveMany($inventories);

    }
}
