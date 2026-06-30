<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * FAIL-FAST: Junction ve request kategori_id eşleşmedi.
 *
 * SAB Kural 3: kategori_id yalnızca consistency guard'dır.
 * Eşleşme yoksa FAIL-FAST, fallback YASAK.
 *
 * @see docs/adr/2026-02-22-junction-first-resolver.md
 */
class TemplateCategoryMismatchException extends RuntimeException
{
    public function __construct(
        public readonly int $junctionId,
        public readonly int $requestKategoriId,
        public readonly ?int $junctionKategoriId,
        string $message = '',
    ) {
        parent::__construct(
            $message ?: sprintf(
                'CategoryMismatch: junction_id=%d junction.kategori_id=%s request.kategori_id=%d — fallback yasak.',
                $junctionId,
                $junctionKategoriId ?? 'null',
                $requestKategoriId,
            )
        );
    }

    public function context(): array
    {
        return [
            'junction_id'          => $this->junctionId,
            'request_kategori_id'  => $this->requestKategoriId,
            'junction_kategori_id' => $this->junctionKategoriId,
        ];
    }
}
