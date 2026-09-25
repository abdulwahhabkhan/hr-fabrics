<?php

namespace App\Http\Resources\Reports;

use App\Models\Accounts\JournalLedger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin JournalLedger */
class AccountsResource extends JsonResource
{
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
