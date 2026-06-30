<?php

namespace App\Policies;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Models\Kisi;
use App\Models\User;

/**
 * Context7: İlan Favori Policy
 * - Favoriye ekleme/çıkarma yetkilendirmesi
 * - Favori listesi görüntüleme
 */
class FavoriPolicy
{
    /**
     * Favoriye ekleme/çıkarma işlemini yap
     * - Eğer $kisi User ile bağlıysa, sadece kendi favorileri
     * - Admin ve danisman tüm favorileri yönetebilir
     */
    public function toggle(User $user, Ilan $ilan, Kisi $kisi): bool
    {
        // Admin
        if ($user->hasRole('admin')) {
            return true;
        }

        // Danışman
        if ($user->hasRole('danisman')) {
            return true;
        }

        // Eğer $kisi user'a bağlıysa, sadece kendi favorileri
        if ($kisi->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Favori listesini görüntüleme
     */
    public function viewFavorilar(User $user, Kisi $kisi): bool
    {
        // Admin ve danisman tüm favorileri görebilir
        if ($user->hasRole(['admin', 'danisman'])) {
            return true;
        }

        // Kişi kendi favorileri görebilir
        if ($kisi->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * İlan detayında favoriye ekleme butonu göster
     */
    public function showToggleButton(User $user, Ilan $ilan): bool
    {
        // İlan yayınlıysa herkese göster
        if ($ilan->yayin_durumu === IlanDurumu::YAYINDA->value) {
            return true;
        }

        // İlanın danışmanı ise kendi ilanını görebilir
        if ($ilan->danisman_id === $user->id) {
            return true;
        }

        // Admin görebilir
        if ($user->hasRole('admin')) {
            return true;
        }

        return false;
    }
}
