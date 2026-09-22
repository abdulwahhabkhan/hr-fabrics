<?php

namespace Database\Seeders;

use App\Models\Purchase\FabricReceivingItem;
use App\Models\Sales\OrderItem;
use App\Models\Stock\Stock;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pos = FabricReceivingItem::all();
        foreach ($pos as $row) {
            Stock::factory()->count(1)->create([
                'type' => 'PO',
                'record_id' => $row->id,
                'ref_no' => $row->fabric_receiving_id,
                'product_id' => $row->product->id,
                'sku' => $row->product->name,
                'qty' => $row->total_qty,
                'unit' => $row->unit,
                'size' => $row->size,
            ]);
        }

        $sos = OrderItem::all();
        foreach ($sos as $row) {
            Stock::factory()->count(1)->create([
                'type' => 'SO',
                'record_id' => $row->id,
                'ref_no' => $row->order_id,
                'product_id' => $row->product->id,
                'sku' => $row->product->name,
                'qty' => -1 * $row->total_qty,
                'unit' => $row->unit,
                'size' => $row->size,
            ]);
        }
    }
}
