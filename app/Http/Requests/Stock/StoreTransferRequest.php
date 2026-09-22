<?php

namespace App\Http\Requests\Stock;

use App\Enums\PaymentMode;
use App\Enums\StoreTransferType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
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
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'account' => ['required', 'array'],
            'type' => ['sometimes', Rule::enum(StoreTransferType::class)],
            'status' => ['sometimes', 'numeric'],
            'payment_mode' => ['sometimes', 'nullable', Rule::enum(PaymentMode::class)],
            'notes' => ['sometimes', 'nullable', 'string'],
            'expenses' => ['sometimes', 'numeric'],
            'discount_on_total' => ['sometimes', 'numeric'],
        ];
    }
}
