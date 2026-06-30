<?php

namespace App\Exceptions;

use Exception;

/**
 * Reality Check Exception
 *
 * Context7: Neural Handshake protokolü için veri tutarsızlığı hatası
 * TKGM verisi ile mevcut kayıt arasında uyumsuzluk tespit edildiğinde fırlatılır
 *
 * [PROSES_MÜHRÜ: YALIHAN_AI_0206]
 */
class RealityCheckException extends Exception
{
    protected $conflictingData;

    public function __construct(string $message, array $conflictingData = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->conflictingData = $conflictingData;
    }

    public function getConflictingData(): array
    {
        return $this->conflictingData;
    }
}
