<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'yayin_tipi_id' => 'required|exists:yayin_tipi_sablonlari,id',
            'category_name' => 'required|string',
        ];
    }
}
