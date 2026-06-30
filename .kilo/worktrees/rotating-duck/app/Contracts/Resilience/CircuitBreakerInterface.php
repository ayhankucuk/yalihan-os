<?php

namespace App\Contracts\Resilience;

/**
 * Circuit Breaker Interface
 *
 * SAB v4.1 - Service Resilience Contract
 * Context7: Harici API çağrılarında devre kesici deseni
 */
interface CircuitBreakerInterface
{
    /**
     * Servisin mevcut circuit state'ini döndür
     *
     * @param string $serviceName  Servis adı (ör: 'tkgm_api', 'turkiye_api')
     * @return string  'closed' | 'open' | 'half-open'
     */
    public function getState(string $serviceName): string;

    /**
     * Servisin kullanılabilir olup olmadığını kontrol et
     *
     * @param string $serviceName  Servis adı
     * @return bool  true = istek gönderilebilir, false = circuit açık
     */
    public function isAvailable(string $serviceName): bool;

    /**
     * Başarılı istek bildirimi (circuit'i kapatmaya yardımcı olur)
     *
     * @param string $serviceName  Servis adı
     * @return void
     */
    public function success(string $serviceName): void;

    /**
     * Başarısız istek bildirimi (hata eşiği aşılırsa circuit açılır)
     *
     * @param string $serviceName  Servis adı
     * @return void
     */
    public function failure(string $serviceName): void;
}
