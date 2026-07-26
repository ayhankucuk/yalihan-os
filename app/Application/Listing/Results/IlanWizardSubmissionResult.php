<?php

declare(strict_types=1);

namespace App\Application\Listing\Results;

/**
 * IlanWizardSubmissionResult
 *
 * Sprint 12C Wave 2: IlanWizardController migration
 *
 * Application-layer result object for wizard submission.
 * HTTP-independent — does not contain JsonResponse or RedirectResponse.
 */
final readonly class IlanWizardSubmissionResult
{
    public function __construct(
        public bool $success,
        public ?int $ilanId = null,
        public ?string $error = null,
        public int $errorCode = 200,
        public array $extra = [],
    ) {}

    /**
     * Create success result
     */
    public static function success(int $ilanId, array $extra = []): self
    {
        return new self(
            success: true,
            ilanId: $ilanId,
            extra: $extra,
        );
    }

    /**
     * Create error result
     */
    public static function error(string $message, int $code = 422, array $extra = []): self
    {
        return new self(
            success: false,
            error: $message,
            errorCode: $code,
            extra: $extra,
        );
    }

    /**
     * Create duplicate submission error
     */
    public static function duplicateSubmission(): self
    {
        return self::error('Bu form zaten gönderildi. Lütfen sayfayı yenileyiniz.', 409);
    }

    /**
     * Create duplicate fingerprint error
     */
    public static function duplicateFingerprint(): self
    {
        return self::error('Aynı ilan bilgileriyle tekrar gönderim yapılamaz.', 409);
    }

    /**
     * Create validation error
     */
    public static function validationError(string $message, array $errors = []): self
    {
        return self::error($message, 422, ['errors' => $errors]);
    }

    /**
     * Create incomplete wizard error
     */
    public static function incompleteWizard(): self
    {
        return self::error('Tüm zorunlu aşamalar tamamlanmamıştır.', 422);
    }

    /**
     * Create template not found error
     */
    public static function templateNotFound(): self
    {
        return self::error('Geçerli bir yayın tipi şablonu bulunamadı. İlan oluşturulamaz.', 422);
    }

    /**
     * Create template category mismatch error
     */
    public static function templateCategoryMismatch(): self
    {
        return self::error('Seçilen şablon bu kategori ile uyuşmuyor.', 422);
    }

    /**
     * Create publication type not allowed error
     */
    public static function publicationTypeNotAllowed(): self
    {
        return self::error('Seçilen yayın tipi bu kategori için izin verilmiyor.', 422);
    }

    /**
     * Create server error result
     */
    public static function serverError(string $message, ?\Throwable $exception = null): self
    {
        $extra = [];
        if ($exception) {
            $extra['exception'] = get_class($exception);
        }

        return self::error('İlan oluşturulamadı: ' . $message, 500, $extra);
    }

    /**
     * Check if result indicates success
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if result indicates duplicate submission
     */
    public function isDuplicateSubmission(): bool
    {
        return !$this->success && $this->errorCode === 409;
    }

    /**
     * Check if result indicates validation error
     */
    public function isValidationError(): bool
    {
        return !$this->success && $this->errorCode === 422;
    }

    /**
     * Check if result indicates server error
     */
    public function isServerError(): bool
    {
        return !$this->success && $this->errorCode === 500;
    }
}
