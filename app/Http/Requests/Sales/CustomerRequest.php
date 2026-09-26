<?php

namespace App\Http\Requests\Sales;

use App\Enums\DiscountType;
use App\Models\City;
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
            'limit' => ['required_if_accepted:credit', 'numeric', 'min:0'],
            'credit' => ['bool', 'required'],
        ];
    }

    /**
     * A credit limit only applies to customers allowed credit; cash-only
     * customers always store a zero limit.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->boolean('credit')) {
            $this->merge(['limit' => 0]);
        }
    }

    protected function passedValidation(): void
    {
        $address = $this->input('address', []);
        if (! empty($address['city'] ?? null)) {
            $address['city_urdu'] = City::where('name', $address['city'])->pluck('name_urdu');
            $this->merge(['address' => $address]);
            $this->getValidatorInstance()->setData($this->all());
        }
    }
}
