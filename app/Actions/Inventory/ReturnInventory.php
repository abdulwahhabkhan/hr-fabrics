<?php

namespace App\Actions\Inventory;

use App\Models\Model;
use App\Models\Stock\Inventory;
use Throwable;

class ReturnInventory
{
    public Inventory $inventory;

    public int $quantity;

    public int $meters;

    public Model $outBound;

    public int $outBoundItemId = 0;

    public function setInventory(Inventory $inventory): self
    {
        $this->inventory = $inventory;

        return $this;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setMeters(int $meters): self
    {
        $this->meters = $meters;

        return $this;
    }

    public function setOutBound(Model $outBound): self
    {
        $this->outBound = $outBound;

        return $this;
    }

    public function setOutBoundItemId(int $outBoundItemId): self
    {
        $this->outBoundItemId = $outBoundItemId;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function process(): Inventory
    {

        $inventory = $this->splitLine();
        $inventory->outbound()->associate($this->outBound);
        $inventory->outbound_item_id = $this->outBoundItemId;
        $inventory->save();

        return $inventory;

    }

    public function splitLine(): Inventory
    {
        if ($this->quantity === $this->inventory->qty) {
            return $this->inventory;
        }
        // Replicate the existing row to create a new line for the allocated portion
        $new = $this->inventory->replicate();
        $new->qty = $this->quantity;
        $new->meters = $this->meters;
        $new->save();

        // Reduce the original line
        $this->inventory->qty -= $this->quantity;
        $this->inventory->meters -= $this->meters;
        $this->inventory->save();

        return $new;

    }
}
