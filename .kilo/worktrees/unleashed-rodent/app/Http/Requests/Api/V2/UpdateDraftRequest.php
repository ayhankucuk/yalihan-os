<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Draft Form Request - Validation for Draft updates
 * 
 * Context7: 100% Compliant
 */
class UpdateDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ai_response' => 'sometimes|string|min:20',
            'ai_model_used' => 'sometimes|in:gpt4,gpt35,deepseek,gemini,llama2,ollama',
            'ai_prompt_version' => 'sometimes|string',
            'metadata' => 'sometimes|array|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'ai_response.min' => 'AI Yanıtı en az 20 karakter olmalıdır.',
            'ai_model_used.in' => 'Geçersiz AI model.',
        ];
    }
}
