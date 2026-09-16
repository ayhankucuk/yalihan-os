<?php

namespace App\Domain\Ilan\Exceptions;

use DomainException;

/**
 * ⚠️ WizardIncompleteException
 *
 * İlan sihirbazının zorunlu adımları tamamlanmadan yayına alma girişiminde fırlatılır.
 */
class WizardIncompleteException extends DomainException
{
    public function __construct(
        string $message = 'İlanın zorunlu sihirbaz adımları tamamlanmadan yayına alınamaz.',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
