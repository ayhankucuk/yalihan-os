<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->baslik ?? 'Danışman',
            'phone' => $this->telefon ?? $this->phone_number, // Handle different field names if any
            'email' => $this->email,
            'avatar' => $this->profile_photo_url,
            'whatsapp' => $this->whatsapp_numara,
        ];
    }
}
