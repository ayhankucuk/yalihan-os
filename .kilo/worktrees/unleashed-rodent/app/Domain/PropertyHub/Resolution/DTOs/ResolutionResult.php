<?php

declare(strict_types=1);

namespace App\Domain\PropertyHub\Resolution\DTOs;

/**
 * Immutable Result for Deterministic Template Resolution.
 *
 * DESIGN RULES:
 * - Readonly properties only.
 * - Contains the FULL resolved state.
 * - Includes the 'signature' hash for integrity verification.
 */
final class ResolutionResult
{
    public function __construct(
        public readonly int $templateId,
        public readonly array $features, // List of ResolvedFeature DTOs (to be defined)
        public readonly array $fieldDependencies, // Resolved field dependencies (from legacy pivot)
        public readonly string $signature, // SHA256 signature of the result
        public readonly string $outputSignature, // Alias for signature used in skeleton
        public readonly array $trace = [], // Debug trace of how the decision was made
        public readonly ?array $meta = [], // Additional metadata (e.g. processing time)
    ) {}

    /**
     * Compute the signature of the result content.
     * This signature MUST match the 'signature' property if the object is valid.
     *
     * @return string
     */
    public function computeSignature(): string
    {
        // Sort features to ensure consistent hashing
        $sortedFeatures = $this->features;
        if (isset($sortedFeatures[0]['display_order'])) {
          unset($sortedFeatures[0]['display_order']);
        }
        usort($sortedFeatures, fn($a, $b) => strcmp($a['slug'] ?? '', $b['slug'] ?? ''));

        // Sort dependencies
        $sortedDependencies = $this->fieldDependencies;
        usort($sortedDependencies, fn($a, $b) => strcmp($a['field_name'] ?? '', $b['field_name'] ?? ''));

        $data = [
            'tpl' => $this->templateId,
            'feat' => $sortedFeatures,
            'dep' => $sortedDependencies,
        ];

        return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * Verify that the signature matches the content.
     */
    public function isValid(): bool
    {
        return $this->signature === $this->computeSignature();
    }

    /**
     * Create a ResolutionResult from legacy V2 data.
     */
    public static function fromV2(array $v2, ResolutionContext $context): self
    {
        $templateId = (int) ($v2['template_id'] ?? 0);
        $features = $v2['features'] ?? [];
        $rules = $v2['validation']['rules'] ?? [];

        // Temporary instance to compute signature
        $temp = new self(
            templateId: $templateId,
            features: $features,
            fieldDependencies: $rules,
            signature: '',
            outputSignature: '',
            trace: ['engine' => 'V2']
        );

        $signature = $temp->computeSignature();

        return new self(
            templateId: $templateId,
            features: $features,
            fieldDependencies: $rules,
            signature: $signature,
            outputSignature: $signature,
            trace: ['engine' => 'V2']
        );
    }
}
