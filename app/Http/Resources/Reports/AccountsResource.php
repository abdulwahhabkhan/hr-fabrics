<?php

namespace App\Http\Resources\Reports;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class AccountsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $balance = $this->total_dr - $this->total_cr;

        return [
            'name' => $this->name,
            'city' => $this->city,
            'type' => ucfirst($this->type),
            'total_cr' => $this->total_cr,
            'total_dr' => $this->total_dr,
            'balance' => $balance,
        ];
    }
}
