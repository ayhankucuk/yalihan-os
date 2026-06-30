<?php

namespace App\Services\AI;

use App\Models\IlanFotograf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VisionTaggingService
{
    protected $aiService;

    public function __construct(YalihanCortex $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Analyze a photo and return tags, condition, and alt text.
     *
     * @param mixed $photo IlanFotograf or Photo model
     * @return array Result of analysis
     */
    public function analyze($photo): array
    {
        $path = $photo->dosya_yolu ?? $photo->url ?? null;
        if (!$path) {
            return ['success' => false, 'message' => 'File path not found'];
        }

        // 1. Prepare Image for Analysis (Base64 encoding if needed for API)
        // For now, we simulate the analysis or use filename heuristics if API is unavailable.

        $analysis = $this->mockAnalysis($path, $photo);

        // 2. Save Results to Metadata (if model supports it)
        if (method_exists($photo, 'update')) {
            // Check if 'ai_tags' or 'metadata' column exists
            // We'll assume metadata field for flexible storage
            $meta = $photo->metadata ?? [];
            $meta['vision_analysis'] = $analysis;
            $photo->metadata = $meta;

            // Also update Alt Text if column exists
            if (in_array('alt_text', $photo->getFillable())) {
                $photo->alt_text = $analysis['alt_text'];
            }

            $photo->save();
        }

        return [
            'success' => true,
            'data' => $analysis
        ];
    }

    /**
     * Mock Analysis - Replaces external API for now.
     * Uses filename keywords and heuristics to simulate Restb.ai features.
     */
    private function mockAnalysis(string $path, $photo): array
    {
        $filename = strtolower(basename($path));

        // Room Detection Logic (Heuristic)
        $roomType = 'Unknown';
        if (str_contains($filename, 'salon') || str_contains($filename, 'living')) $roomType = 'Living Room';
        elseif (str_contains($filename, 'mutfak') || str_contains($filename, 'kitchen')) $roomType = 'Kitchen';
        elseif (str_contains($filename, 'yatak') || str_contains($filename, 'bed')) $roomType = 'Bedroom';
        elseif (str_contains($filename, 'banyo') || str_contains($filename, 'bath')) $roomType = 'Bathroom';
        elseif (str_contains($filename, 'bahce') || str_contains($filename, 'garden')) $roomType = 'Garden';
        elseif (str_contains($filename, 'dis') || str_contains($filename, 'facade')) $roomType = 'Exterior';

        // Condition Analysis (Heuristic based on keywords or random if testing)
        // In real API, this comes from visual processing
        $condition = 'Standard';
        if (str_contains($filename, 'luks') || str_contains($filename, 'luxury')) $condition = 'Luxury';
        elseif (str_contains($filename, 'tadilat')) $condition = 'Fixer Upper';

        // Feature Extraction
        $features = [];
        if ($roomType === 'Kitchen') $features = ['Cabinets', 'Countertop', 'Sink'];
        if ($roomType === 'Living Room') $features = ['Window', 'Flooring', 'Lighting'];

        // Alt Text Generation
        $altText = "Modern {$condition} {$roomType} with natural lighting.";
        if ($roomType === 'Exterior') {
            $altText = "Exterior view of the property showing structure and design.";
        }

        return [
            'room_type' => $roomType,
            'condition' => $condition,
            'features' => $features,
            'alt_text' => $altText,
            'confidence' => 0.85 // Mock confidence
        ];
    }
}
