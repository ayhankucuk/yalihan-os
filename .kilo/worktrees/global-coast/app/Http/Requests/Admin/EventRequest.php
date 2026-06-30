<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'superadmin', 'danisman']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'event_time' => 'required',
            'type' => 'required|in:meeting,viewing,call,followup,other',
            'location' => 'nullable|string|max:255',
            'attendees' => 'nullable|string',
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'start' => 'nullable|date',
            'end' => 'nullable|date|after:start',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Etkinlik başlığı zorunludur.',
            'title.max' => 'Başlık en fazla 255 karakter olabilir.',
            'event_date.required' => 'Etkinlik tarihi zorunludur.',
            'event_date.date' => 'Geçerli bir tarih giriniz.',
            'event_time.required' => 'Etkinlik saati zorunludur.',
            'type.required' => 'Etkinlik tipi seçimi zorunludur.',
            'type.in' => 'Geçersiz etkinlik tipi.',
            'location.max' => 'Konum en fazla 255 karakter olabilir.',
            'end.after' => 'Bitiş tarihi başlangıç tarihinden sonra olmalıdır.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // event_date ve start/end alanlarını birleştir
        if ($this->has('start') && ! $this->has('event_date')) {
            $this->merge([
                'event_date' => $this->start,
            ]);
        }

        if ($this->has('end') && ! $this->has('check_out')) {
            $this->merge([
                'check_out' => $this->end,
            ]);
        }
    }
}
