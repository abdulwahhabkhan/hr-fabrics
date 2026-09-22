<?php

namespace App\Actions\Inventory;

use App\Enums\PackingType;
use App\Exceptions\UnableToAllocateStockException;
use App\Models\Catalog\Product;
use App\Models\Model;
use App\Models\Stock\Inventory;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class IssueInventory
{
    public Model $outbound;

    public int $outboundItemId = 0;

    protected int $quantity = 0;

    protected float $size = 0.0;

    protected float $meters = 0.0;

    protected int $productId;

    protected PackingType $unit;

    private CarbonInterface $transactionDate;

    private ?CarbonInterface $receivedBefore = null;

    private ?CarbonInterface $receivedAfter = null;

    public function setReceivedAfter(CarbonInterface $date): self
    {
        $this->receivedAfter = $date;

        return $this;
    }

    public function setTransactionDate(CarbonInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;

        return $this;
    }

    public function setReceivedBefore(CarbonInterface $date): self
    {
        $this->receivedBefore = $date;

        return $this;
    }

    public function setSize(float $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function setMeters(float $meters): self
    {
        $this->meters = $meters;

        return $this;
    }

    public function setUnit(PackingType $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setOutbound(Model $morph): self
    {
        $this->outbound = $morph;

        return $this;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;

        return $this;
    }

    public function setOutboundItemId(int $outboundItemId): self
    {
        $this->outboundItemId = $outboundItemId;

        return $this;
    }

    /**
     * @throws UnableToAllocateStockException
     */
    public function issue(): array
    {
        $stocks = $this->getProductStock();
        $remMeters = $this->meters;
        $remQty = $this->quantity;
        $stockIssued = [];
        if ($stocks->sum('meters') < $remMeters) {
            $product = $this->productInfo();
            throw ValidationException::withMessages([
                'product' => "Order {$this->outboundItemId}, Not enough stock for product {$product->id} : {$product->name}, size: {$this->size}, qty: $remQty, meters: $remMeters",
            ]);
        }
        foreach ($stocks as $stock) {
            if ($remMeters <= 0) {
                break;
            }

            $stockUsed = $this->splitLine($stock, $remQty, $remMeters);
            $stockUsed->outbound()->associate($this->outbound);
            $stockUsed->outbound_item_id = $this->outboundItemId;
            $stockUsed->outbound_on = $this->transactionDate;
            if ($stockUsed->size !== $this->size) {
                $stockUsed->size = $this->size;
            }
            $stockUsed->save();
            $stock->save();
            $stockIssued[] = $stockUsed;
            $remMeters = $remMeters - $stockUsed->meters;
        }

        $allocatedSum = collect($stockIssued)->sum('meters');
        if ($allocatedSum !== $this->meters) {
            throw UnableToAllocateStockException::unableToAllocate($this->productInfo()->name, $this->meters,
                $allocatedSum);
        }

        return $stockIssued;
    }

    protected function splitLine(Inventory $inventory, int $qty, float $meters): Inventory
    {
        if ($meters >= $inventory->meters) {
            return $inventory;
        }
        $new = $inventory->replicate(['qty', 'meters']);
        $new->meters = $meters;
        $inventory->meters = $inventory->meters - $meters;
        if ($this->unit !== PackingType::Thaan) {
            $inventory->qty = $inventory->qty - $qty;
            $new->qty = $qty;
        }

        return $new;
    }

    private function productInfo(): Product|Closure
    {
        return once(fn () => Product::find($this->productId));
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private function getProductStock(): Collection
    {
        return Inventory::query()
            ->where('product_id', $this->productId)
            ->where('unit', $this->unit)
            ->when($this->unit !== PackingType::Thaan, function ($query) {
                $query->where('size', $this->size);
            })
            ->when($this->receivedBefore, function ($query) {
                $query->where('transaction_date', '<=', $this->receivedBefore);
            })
            ->when($this->receivedAfter, function ($query) {
                $query->where('transaction_date', '>=', $this->receivedAfter);
            })
            ->available()
            ->fifo()
            ->get();
    }
}
