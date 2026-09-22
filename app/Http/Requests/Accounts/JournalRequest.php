<?php

namespace App\Http\Requests\Accounts;

use Illuminate\Foundation\Http\FormRequest;

class JournalRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_account' => ['required'],
            'from_account.id' => ['required'],
            'to_account.id' => ['required'],
            'to_account' => ['required', 'different:from_account'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'detail' => ['required', 'max:100'],
            'file' => ['nullable'],
        ];
    }
}
