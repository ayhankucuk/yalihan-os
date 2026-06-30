<?php

declare(strict_types=1);

namespace App\DTOs\PropertyPipeline;

/**
 * PropertyPipelineResult — P01 Sprint 4.1
 *
 * Immutable value object returned by CreatePropertyPipeline::run().
 */
final class PropertyPipelineResult
{
    public function __construct(
        public readonly bool $success,
        public readonly int $propertyId,
        public readonly bool $descriptionGenerated,
        public readonly bool $photosProcessed,
        public readonly bool $driveFolderCreated,
        public readonly bool $notificationSent,
        public readonly int $executionTimeMs,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = [],
    ) {}

    public static function success(
        int $propertyId,
        bool $descriptionGenerated,
        bool $photosProcessed,
        bool $driveFolderCreated,
        bool $notificationSent,
        int $executionTimeMs,
    ): self {
        return new self(
            success: true,
            propertyId: $propertyId,
            descriptionGenerated: $descriptionGenerated,
            photosProcessed: $photosProcessed,
            driveFolderCreated: $driveFolderCreated,
            notificationSent: $notificationSent,
            executionTimeMs: $executionTimeMs,
        );
    }

    public static function failure(string $message, int $executionTimeMs): self
    {
        return new self(
            success: false,
            propertyId: 0,
            descriptionGenerated: false,
            photosProcessed: false,
            driveFolderCreated: false,
            notificationSent: false,
            executionTimeMs: $executionTimeMs,
            errorMessage: $message,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'property_id' => $this->propertyId,
            'description_generated' => $this->descriptionGenerated,
            'photos_processed' => $this->photosProcessed,
            'drive_folder_created' => $this->driveFolderCreated,
            'notification_sent' => $this->notificationSent,
            'execution_time_ms' => $this->executionTimeMs,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }
}
