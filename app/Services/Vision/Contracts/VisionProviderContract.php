<?php

namespace App\Services\Vision\Contracts;

use App\DTOs\Vision\VisionAnalysisDTO;

/**
 * Vision Provider Interface — Sprint 6.4
 *
 * AI Vision provider abstraction.
 * Never couple business logic directly to GPT-4 Vision.
 *
 * Implementations:
 *   - OpenAIVisionProvider
 *   - MockVisionProvider
 * Future:
 *   - GeminiVisionProvider
 *   - AzureVisionProvider
 *   - LocalVisionProvider
 */
interface VisionProviderContract
{
    /**
     * Bir fotoğrafı AI Vision ile analiz et.
     *
     * @param  string  $imagePath  Mutlak dosya yolu veya public URL
     * @param  array   $context   Ek bağlam: [ilan_id, room_hint, ...]
     * @return VisionAnalysisDTO
     */
    public function analyze(string $imagePath, array $context = []): VisionAnalysisDTO;

    /**
     * Provider adını döner.
     */
    public function providerName(): string;

    /**
     * Provider'ın kullanılabilir olup olmadığını kontrol eder.
     */
    public function isAvailable(): bool;
}
