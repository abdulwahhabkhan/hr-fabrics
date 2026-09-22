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
            'description' => ['max:240'],
            'size' => ['nullable', 'numeric'],
            'cost' => ['required', 'numeric'],
            'unit_price' => ['required', 'numeric'],
            'suit_price' => ['required_if:is_box,0', 'nullable', 'numeric'],
            'finish' => ['required', 'string', 'max:30'],
            'brand_id' => ['required', 'exists:brands,id'],
            'vendor_id' => ['required', 'exists:accounts,id'],
            'is_box' => ['required', 'boolean'],
        ];
    }
}
