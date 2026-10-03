<?php

namespace App\Http\Requests;

use App\Enums\DirectoryType;
use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $fileable = $this->fileable();
        if (! $fileable) {
            return true;
        }

        return $this->user()->can('attach', [File::class, $fileable]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'mimetypes:image/jpeg,image/png,application/pdf'],
            'directory' => ['required', Rule::enum(DirectoryType::class)],
            'morph_class' => ['sometimes', 'required_with:morph_id', 'string', Rule::in(array_keys(Relation::morphMap()))],
            'morph_id' => ['sometimes', 'required_with:morph_class', 'integer'],
        ];
    }

    /**
     * The record the uploaded file is attached to, if any.
     */
    public function fileable(): ?Model
    {
        $modelClass = Relation::getMorphedModel((string) $this->input('morph_class'));
        $morphId = $this->input('morph_id');
        if (! $modelClass || ! $morphId) {
            return null;
        }

        return once(fn () => $modelClass::query()->findOrFail($morphId));
    }
}
