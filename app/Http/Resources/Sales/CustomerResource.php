<?php

namespace App\Http\Resources\Sales;

use App\Models\Accounts\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $agent = $this->agent;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_urdu' => $this->name_urdu,
            'phone' => $this->phone,
            'email' => $this->email,
            'limit' => $this->limit,
            'credit' => $this->credit,
            'discount' => $this->discount,
            'suspended' => $this->suspended,
            'suspended_at' => $this->suspended_at?->format('d M Y'),
            'discount_type' => $this->discount_type->value,
            'discount_label' => $this->discount_type->valueLabel($this->discount),
            'agent_name' => $agent ? $agent->name : '',
            'address' => $this->address,
            'updated_at' => $this->updated_at,
        ];
    }
}
