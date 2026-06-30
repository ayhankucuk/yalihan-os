<?php

namespace App\Modules\TakimYonetimi\Services;

use App\Models\User;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;

/**
 * TakimService — Application Service
 *
 * SAB v4.1 Kural 1/11: Controller'dan mutation logic taşıması
 */
class TakimService
{
    use GuardsAgentWrites;

    /**
     * Takım üyesi oluştur
     */
    public function store(array $data): User
    {
        $this->blockAgentWrite('store');

        return DB::transaction(function () use ($data) {
            $data['password'] = bcrypt('password123'); // Geçici şifre
            $data['aktiflik_durumu'] = $data['aktiflik_durumu'] ?? true;

            $user = User::create($data);
            $user->assignRole('danisman');
            return $user;
        });
    }
}
