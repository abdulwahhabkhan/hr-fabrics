<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SalesReturnRequest extends FormRequest
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
            'status' => ['required'],
            'payment_mode' => ['required'],
            'expenses' => ['nullable'],
            'discount' => ['nullable'],
            'order_no' => ['required', 'max:20'],
            'items' => ['required', 'array'],
            'items.*.product' => ['array'],
            'items.*.name' => ['required'],
            'items.*.commission' => ['required'],
            'items.*.qty' => ['required'],
            'items.*.unit' => ['required'],
            'items.*.size' => ['required'],
            'items.*.rate' => ['required'],
            'items.*.total_commission' => ['required'],
            'items.*.total_amount' => ['required'],
            'items.*.total_qty' => ['required'],
            // 'bilti_no' => ['required'],
            // 'bill_no' => ['required'],
            'info' => ['nullable'],
        ];
    }
}
