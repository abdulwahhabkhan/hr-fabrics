<?php

namespace App\Http\Requests\Reports;

use App\Actions\Accounts\Period\TransactionBook;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InOutTransactionDetailRequest extends FormRequest
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
            'category' => ['required', Rule::in(array_keys(TransactionBook::categories()))],
            'side' => ['required', Rule::in(['dr', 'cr'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category.in' => 'The selected transaction category is invalid.',
            'side.in' => 'The side must be either debit (dr) or credit (cr).',
        ];
    }
}
