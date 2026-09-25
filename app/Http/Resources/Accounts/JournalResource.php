<?php

namespace App\Http\Resources\Accounts;

use App\Models\Accounts\Journal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Journal
 */
class JournalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $amount = $this->debit > 0 ? $this->debit : $this->credit;

        return [
            'id' => $this->id,
            'date' => $this->posted_at->toDateString(),
            'account' => $this->name.', '.$this->city,
            'head' => $this->head,
            'detail' => $this->detail,
            'amount' => $amount,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'file' => $this->has_file,
            'reference_no' => $this->reference_no,
        ];
    }
}
