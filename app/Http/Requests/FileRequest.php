<?php

namespace App\Http\Requests;

use App\Enums\DirectoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileRequest extends FormRequest
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
            'file' => ['required', 'mimetypes:image/jpeg,image/png,application/pdf'],
            'directory' => ['required', Rule::enum(DirectoryType::class)],
            'morph_class' => ['sometimes', 'string'],
            'morph_id' => ['sometimes', 'integer'],
        ];
    }
}
