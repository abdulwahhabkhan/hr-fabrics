<?php

namespace App\Http\Requests\Stock;

use App\Enums\PackingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConversionRequest extends FormRequest
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
            'product' => ['required'],
            'from.qty' => ['required', 'numeric'],
            'from.size' => ['required', 'numeric'],
            'from.unit' => ['required', Rule::enum(PackingType::class)],
            'to.size' => ['required', 'numeric'],
            'to.qty' => ['required', 'numeric'],
            'to.unit' => ['required', Rule::enum(PackingType::class)],
        ];
    }
}
