<?php

namespace App\Http\Requests\Stock;

use App\Enums\PackingType;
use App\Enums\StockLocation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'location' => ['required', Rule::enum(StockLocation::class)],
            'from.unit' => ['required', Rule::in([PackingType::Thaan->value])],
            'from.qty' => ['required', 'integer', 'min:1'],
            'from.size' => ['required', 'numeric', 'gt:0'],
            'to.unit' => ['required', Rule::in([PackingType::Suit->value, PackingType::Thaan->value])],
            'to.qty' => ['required', 'integer', 'min:1'],
            'to.size' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * The produced meters must use up exactly the thaan meters being cut, so no leftover is lost from stock.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $fromMeters = round((float) $this->input('from.qty') * (float) $this->input('from.size'), 2);
                $toMeters = round((float) $this->input('to.qty') * (float) $this->input('to.size'), 2);

                if ($toMeters !== $fromMeters) {
                    $validator->errors()->add(
                        'to.qty',
                        "Produced meters ({$toMeters}) must equal the thaan meters being cut ({$fromMeters}), leftover is not allowed."
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'from.unit' => 'source unit',
            'from.qty' => 'number of thaans',
            'from.size' => 'meters per thaan',
            'to.unit' => 'conversion type',
            'to.qty' => 'produced quantity',
            'to.size' => 'meters per piece',
        ];
    }
}
