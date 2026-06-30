<?php

namespace App\Services\AI\Vision\Contracts;

/**
 * Interface for AI Vision Providers
 */
interface VisionAnalysisContract
{
    /**
     * Analyze images and extract property features
     * 
     * @param array $imageUrls List of absolute URLs or public paths to images
     * @param array $context ['kategori_id' => int, 'allowed_slugs' => array, ...]
     * @return array Standardized result with features, reasons, and signals
     */
    public function analyze(array $imageUrls, array $context = []): array;
}
