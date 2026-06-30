<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\AI\DeepSeekModel;

class UpdateDeepSeekSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'api_key' => ['nullable', 'string', 'starts_with:sk-'],
            'model' => [
                'required',
                'string',
                Rule::in(DeepSeekModel::values()),
            ],
        ];
    }

    public function canonicalModel(): string
    {
        return $this->validated('model');
    }
}
