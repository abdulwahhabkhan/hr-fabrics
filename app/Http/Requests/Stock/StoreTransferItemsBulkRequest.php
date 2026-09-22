<?php

namespace App\Http\Requests\Stock;

use App\Enums\PackingType;
use App\Enums\StoreTransferType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferItemsBulkRequest extends FormRequest
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
        return [
            'expense' => ['required', 'numeric', 'min:0'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'rows.*.unit' => ['required', Rule::enum(PackingType::class)],
            'rows.*.size' => ['required', 'numeric', 'min:0'],
            'rows.*.cost' => ['required', 'numeric', 'min:0'],
            'rows.*.qty' => ['required', 'integer', 'min:0'],
            'rows.*.meters' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $storeTransfer = $this->route('storeTransfer');

            if ($storeTransfer?->type !== StoreTransferType::Store->value) {
                $validator->errors()->add('rows', 'Loading stock is only available for store transfers.');
            }

            if ($storeTransfer?->is_closed) {
                $validator->errors()->add('rows', 'This store transfer is already confirmed and closed.');
            }
        });
    }
}
