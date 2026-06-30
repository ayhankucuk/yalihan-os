<?php

namespace App\Domain\Core\Security;

/**
 * Class SignatureSealEngine
 * @package App\Domain\Core\Security
 * @description Phase 20: Deterministik sıralama ve HMAC-SHA512 ile veri yapılarının kriptografik mühür doğruluğunu sağlayan concrete motor.
 */
class SignatureSealEngine implements SignatureSealEngineContract
{
    /**
     * Payload ve tuz parametrelerine göre SHA-512 kriptografik imza mühür üretir.
     *
     * @param array $payload
     * @param string $salt
     * @return string
     */
    public function generateSeal(array $payload, string $salt): string
    {
        $serialized = $this->deterministicSerialize($payload);
        $secretKey = config('yalihan.fortress_secure_salt.kripto_anahtar', 'fallback_salt_2026') . '|' . $salt;
        return hash_hmac('sha512', $serialized, $secretKey);
    }

    /**
     * Verilen mühür imzasının, payload ve tuz ile uyuşup uyuşmadığını doğrular.
     *
     * @param array $payload
     * @param string $seal
     * @param string $salt
     * @return bool
     */
    public function verifySeal(array $payload, string $seal, string $salt): bool
    {
        $calculated = $this->generateSeal($payload, $salt);
        return hash_equals($calculated, $seal);
    }

    /**
     * Diziyi deterministik (anahtarlara göre sıralı) hale getirir.
     */
    private function deterministicSerialize(array $data): string
    {
        ksort($data);
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                // Rekürsif serileştirme
                $value = $this->deterministicSerialize($value);
            }
        }
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
