<?php

namespace App\Http\Requests\Stock;

use App\Enums\StoreTransferType;
use App\Rules\CheckInventoryRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransferItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $verifyQty = ['required', 'numeric'];
        $storeTransfer = $this->route('storeTransfer');
        if ($storeTransfer?->type === StoreTransferType::Store->value && ! $this->boolean('oversold')) {
            $verifyQty[] = new CheckInventoryRule;
        }

        return [
            'item_id' => ['nullable', 'integer'],
            'product' => ['required', 'array'],
            'unit' => ['required', 'string'],
            'size' => ['required', 'numeric'],
            'qty' => $verifyQty,
            'price' => ['required', 'numeric'],
            'expense' => ['required', 'numeric'],
        ];
    }
}
