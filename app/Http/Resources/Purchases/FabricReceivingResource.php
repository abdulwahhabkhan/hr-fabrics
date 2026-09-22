<?php

namespace App\Http\Resources\Purchases;

use App\Models\Purchase\FabricReceiving;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FabricReceiving
 *
 * @property FabricReceiving $resource
 */
final class FabricReceivingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {

        return [
            'can' => [
                'view' => $request->user()->can('view', $this->resource),
                'edit' => $request->user()->can('edit', $this->resource),
                'unlock' => $request->user()->can('unlock', $this->resource),
                'delete' => $request->user()->can('delete', $this->resource),
                'inventory' => $request->user()->can('inventory', $this->resource),
            ],
            'transaction_date' => $this->transaction_display_date->toDateString(),
            $this->merge(parent::toArray($request)),
        ];
    }
}
