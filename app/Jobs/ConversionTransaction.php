<?php

namespace App\Jobs;

use App\Enums\Module;
use App\Models\Stock\Conversion;
use App\Models\Stock\Inventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ConversionTransaction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(protected Conversion $conversion) {}

    public function handle(): void
    {
        $this->processConversion();
        $this->processConversion(false);

    }

    public function processConversion($add = true): void
    {
        $multiple = -1;

        $product = $this->conversion->from;

        $size = 0;
        if (! $add) {
            $product = $this->conversion->to;
            $size = $product['size'];
            $multiple = 1;
        }

        $unit = $product['unit'];

        Inventory::create([
            'type' => 'MC',
            'record_id' => $this->conversion->id,
            'ref_no' => $this->conversion->id,
            'product_id' => $this->conversion->product_id,
            'unit' => $unit,
            'size' => $size,
            'info' => ['module' => Module::Conversion->value, 'type' => Module::Conversion->value],
            'qty' => $multiple * $product['qty'],
            'meters' => $multiple * $product['qty'] * $product['size'],
        ]);
    }
}
