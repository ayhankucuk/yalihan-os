<?php

namespace App\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Draft Form Request - Validation for AI Draft creation
 * 
 * Context7: 100% Compliant
 * - Field names: ai_response, ai_model_used, ai_prompt_version, taslak_durumu
 * - AI model enum: gpt4, gpt35, deepseek, gemini, llama2, ollama
 */
class StoreDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'ai_response' => 'required|string|min:20',
            'ai_model_used' => 'required|in:gpt4,gpt35,deepseek,gemini,llama2,ollama',
            'ai_prompt_version' => 'required|string',
            'metadata' => 'sometimes|array',
        ];
    }

    public function messages(): array
    {
        return [
            'ai_response.required' => 'AI Yanıtı zorunludur.',
            'ai_response.min' => 'AI Yanıtı en az 20 karakter olmalıdır.',
            'ai_model_used.required' => 'AI Model alanı zorunludur.',
            'ai_model_used.in' => 'Geçersiz AI model. Geçerli modeller: gpt4, gpt35, deepseek, gemini, llama2, ollama',
            'ai_prompt_version.required' => 'Prompt Version zorunludur.',
        ];
    }
}
