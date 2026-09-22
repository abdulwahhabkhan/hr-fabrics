<?php

namespace App\Actions\Inbound\FabricReceiving;

use App\Enums\StatusText;
use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\Request;

final class CreateInbound
{
    public function handle(Request $request): FabricReceiving
    {
        $supplier = $request->input('supplier');
        $invoice_sr = FabricReceiving::query()->max('id');
        $invoice_sr++;
        $invoice_no = 'ASN-'.date('ym').str($invoice_sr)->padLeft(3, '0');
        $created_by = $request->user()->id;

        return FabricReceiving::query()->create([
            'created_by' => $created_by,
            'supplier_id' => $supplier['supplier_id'],
            'invoice_no' => $invoice_no,
            'total_qty' => 0,
            'total_meters' => 0,
            'status' => StatusText::Open->value,
        ]);

    }
}
