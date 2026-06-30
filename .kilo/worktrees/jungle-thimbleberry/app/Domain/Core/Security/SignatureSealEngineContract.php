<?php

namespace App\Domain\Core\Security;

/**
 * Interface SignatureSealEngineContract
 * @package App\Domain\Core\Security
 * @description Phase 20: Çekirdek veri yapıları için HMAC-SHA512 tabanlı kriptografik imza ve mühür üreten arayüz.
 */
interface SignatureSealEngineContract
{
    /**
     * Payload ve tuz parametrelerine göre SHA-512 kriptografik imza mühür üretir.
     *
     * @param array $payload
     * @param string $salt
     * @return string
     */
    public function generateSeal(array $payload, string $salt): string;

    /**
     * Verilen mühür imzasının, payload ve tuz ile uyuşup uyuşmadığını doğrular.
     *
     * @param array $payload
     * @param string $seal
     * @param string $salt
     * @return bool
     */
    public function verifySeal(array $payload, string $seal, string $salt): bool;
}
