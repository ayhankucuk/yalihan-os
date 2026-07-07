<?php

namespace App\DTOs;

/**
 * DriveWorkspaceResult DTO
 *
 * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
 *
 * Represents the result of a Google Drive workspace creation operation.
 * Immutable value object — no mutations after construction.
 */
final class DriveWorkspaceResult
{
    /**
     * @param bool $success Whether the workspace creation succeeded
     * @param string|null $rootFolderId Google Drive folder ID of the root workspace
     * @param string|null $rootFolderUrl Google Drive URL of the root workspace
     * @param array<string, string> $subfolders Map of folder name => folder ID
     * @param string|null $errorMessage Error message if success is false
     * @param array $metadata Additional metadata about the operation
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $rootFolderId = null,
        public readonly ?string $rootFolderUrl = null,
        public readonly array $subfolders = [],
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Create a successful result
     *
     * @param array<string, string> $subfolders
     */
    public static function success(
        string $rootFolderId,
        string $rootFolderUrl,
        array $subfolders,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            rootFolderId: $rootFolderId,
            rootFolderUrl: $rootFolderUrl,
            subfolders: $subfolders,
            metadata: $metadata,
        );
    }

    /**
     * Create a failure result
     */
    public static function failure(string $errorMessage, array $metadata = []): self
    {
        return new self(
            success: false,
            errorMessage: $errorMessage,
            metadata: $metadata,
        );
    }

    /**
     * Check if the result is successful
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Get the folder ID for a subfolder by name
     */
    public function getSubfolderId(string $name): ?string
    {
        return $this->subfolders[$name] ?? null;
    }

    /**
     * Get all subfolder names
     *
     * @return array<string>
     */
    public function getSubfolderNames(): array
    {
        return array_keys($this->subfolders);
    }

    /**
     * Get subfolder count
     */
    public function getSubfolderCount(): int
    {
        return count($this->subfolders);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'root_folder_id' => $this->rootFolderId,
            'root_folder_url' => $this->rootFolderUrl,
            'subfolders' => $this->subfolders,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }
}
