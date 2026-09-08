<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AgentResource — IlanDetailResource ile kullanılır.
 *
 * Politika:
 *   - Anonim: yalnız name + avatar
 *   - Auth + ilan_detail_full bayrağı: name + phone + email + whatsapp
 *
 * @see IlanController::show() — $request->attributes->set('ilan_detail_full', true)
 *       bayrağını IlanDetailResource'a geçirmeden önce atar.
 */
class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isFullAccess = (bool) $request->attributes->get('ilan_detail_full', false);

        $base = [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->profile_photo_url,
        ];

        if (!$isFullAccess) {
            return $base;
        }

        return array_merge($base, [
            'title' => $this->baslik ?? 'Danışman',
            'phone' => $this->telefon ?? $this->phone_number,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp_numara,
        ]);
    }
}
