<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
            'description' => ['nullable', 'max:240'],
            'size' => ['numeric'],
            'cost' => ['numeric'],
            'unit_price' => ['numeric'],
            'suit_price' => ['numeric'],
            'finish' => ['required', 'string', 'max:30'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'vendor_id' => ['nullable', 'exists:accounts,id'],
            'is_box' => ['boolean'],
        ];
    }

    /**
     * Default the optional numeric fields, whose columns do not accept null.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'size' => $this->input('size') ?? 0,
            'cost' => $this->input('cost') ?? 0,
            'unit_price' => $this->input('unit_price') ?? 0,
            'suit_price' => $this->input('suit_price') ?? 0,
            'is_box' => $this->input('is_box') ?? 0,
        ]);
    }
}
