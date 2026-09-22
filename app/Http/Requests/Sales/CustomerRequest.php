<?php

namespace App\Http\Requests\Sales;

use App\Enums\DiscountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'agent' => ['nullable'],
            'commission_rate' => ['nullable'],
            'address' => ['nullable'],
            'name' => ['required', 'max:200', Rule::unique('accounts')->ignore($this->customer)],
            'name_urdu' => ['required', 'max:200'],
            'email' => ['nullable', 'email', 'max:200'],
            'phone' => ['nullable', 'max:50'],
            'discount' => ['numeric', 'required'],
            'discount_type' => ['required', Rule::enum(DiscountType::class)],
            'limit' => ['numeric', 'required_if:credit,1'],
            'credit' => ['bool', 'required'],
        ];
    }
}
