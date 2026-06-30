<?php

namespace App\Services\Portal;

/**
 * 🌐 Portal ID Normalizer
 *
 * Farklı emlak portallarından gelen ID formatlarını normalize eder.
 *
 * @author Yalıhan AI
 */
class PortalIdNormalizer
{
    /**
     * Sağlayıcı ID'sini normalize et.
     *
     * @param string $provider
     * @param string $id
     * @return string
     */
    public function normalizeProviderId(string $provider, string $id): string
    {
        $id = trim($id);

        return match ($provider) {
            'sahibinden' => $this->normalizeSahibinden($id),
            'emlakjet' => $this->normalizeEmlakjet($id),
            'hepsiemlak' => $this->normalizeHepsiemlak($id),
            'zingat' => $this->normalizeZingat($id),
            'hurriyetemlak' => $this->normalizeHurriyetemlak($id),
            default => $id,
        };
    }

    private function normalizeSahibinden(string $id): string
    {
        // Sadece rakamları al
        return preg_replace('/[^0-9]/', '', $id);
    }

    private function normalizeEmlakjet(string $id): string
    {
        return $id;
    }

    private function normalizeHepsiemlak(string $id): string
    {
        return $id;
    }

    private function normalizeZingat(string $id): string
    {
        return $id;
    }

    private function normalizeHurriyetemlak(string $id): string
    {
        return $id;
    }
}
