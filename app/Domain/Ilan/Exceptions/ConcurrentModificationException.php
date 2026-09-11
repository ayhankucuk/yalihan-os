<?php

namespace App\Domain\Ilan\Exceptions;

use RuntimeException;

/**
 * 🚨 ConcurrentModificationException
 *
 * İyimser kilit (lock_version) çakışmasında veya eşzamanlı oturum ezilmesinde fırlatılır.
 */
class ConcurrentModificationException extends RuntimeException
{
    public function __construct(
        string $message = 'Oturum başka bir sekmede veya cihazda güncellendi. Lütfen sayfayı yenileyin.',
        int $code = 409,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
