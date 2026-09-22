<?php

namespace App\Actions\Inbound\Return;

use App\Actions\Inventory\IssueInventory;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Purchase\PurchaseReturnItem;

class ReturnStock
{
    public function __construct(protected IssueInventory $issueInventory) {}

    /**
     * @throws UnableToAllocateStockException
     */
    public function handle(PurchaseReturn $return, PurchaseReturnItem $item): void
    {
        $this->issueInventory
            ->setOutbound($return)
            ->setTransactionDate($return->transaction_date)
            ->setOutboundItemId($item->id)
            ->setProductId($item->product_id)
            ->setSize($item->size)
            ->setQuantity($item->qty)
            ->setUnit($item->unit)
            ->setMeters($item->total_qty)
            ->issue();
    }
}
