<?php

namespace App\Http\Requests\Stock;

use App\Enums\PackingType;
use App\Enums\StockLocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockTransferRequest extends FormRequest
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
        $isThaan = $this->input('unit') === PackingType::Thaan->value;

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'unit' => ['required', Rule::enum(PackingType::class)],
            'size' => [Rule::requiredIf(! $isThaan), 'nullable', 'numeric', Rule::when(! $isThaan, ['gt:0'])],
            'qty' => ['required', 'integer', 'min:1'],
            'meters' => [Rule::requiredIf($isThaan), 'nullable', 'numeric', Rule::when($isThaan, ['gt:0'])],
            'from_location' => ['required', Rule::enum(StockLocation::class)],
            'to_location' => ['required', Rule::enum(StockLocation::class), 'different:from_location'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'to_location.different' => 'Stock must move to a different location.',
        ];
    }
}
