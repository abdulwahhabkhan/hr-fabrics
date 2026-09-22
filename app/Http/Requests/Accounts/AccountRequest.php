<?php

namespace App\Http\Requests\Accounts;

use App\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;

class AccountRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'type' => ['string', 'required'],
            'expense_account' => ['nullable', 'required_if:type,'.AccountType::Agent->value],
            'phone' => ['nullable'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'array'],
            'commission_rate' => ['nullable', 'array'],
        ];
    }
}
