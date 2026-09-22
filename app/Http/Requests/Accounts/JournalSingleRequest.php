<?php

namespace App\Http\Requests\Accounts;

use App\Enums\EntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JournalSingleRequest extends FormRequest
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
            'account' => ['required'],
            'account.id' => ['required'],

            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'detail' => ['required', 'max:100'],
            'file' => ['nullable'],
            'type' => ['required', Rule::enum(EntryType::class)],
        ];
    }
}
