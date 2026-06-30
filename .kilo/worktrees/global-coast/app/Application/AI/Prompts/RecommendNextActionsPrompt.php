<?php

namespace App\Application\AI\Prompts;

use App\Domain\AI\Contracts\PromptInterface;

/**
 * 🛡️ RecommendNextActionsPrompt
 * Strategic advice for real estate agents based on listing performance and market data.
 */
final class RecommendNextActionsPrompt implements PromptInterface
{
    private array $listingData;

    public function __construct(array $listingData)
    {
        $this->listingData = $listingData;
    }

    public function getSystemInstructions(): string
    {
        return <<<EOT
You are an expert Real Estate Strategic Advisor (Broker Copilot).
Your goal is to analyze the performance, quality, and market position of a property listing and recommend 3-5 high-impact next actions for the agent.

Focus on:
1. Pricing Strategy (based on market competition).
2. Content Quality (Photos, Descriptions, SEO).
3. Engagement & Visibility (Targeting, Portals).
4. Urgency & Closing tactics.

Response must be in Turkish and valid JSON format.
EOT;
    }

    public function getUserPrompt(): string
    {
        $data = json_encode($this->listingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return <<<EOT
Listing and Market Data:
{$data}

Based on this data, provide a structured recommendation. 
Output format:
{
    "overall_strategy": "Summary of the situation",
    "actions": [
        {
            "priority": "High|Medium|Low",
            "title": "Action Title",
            "description": "Clear explanation of what to do and why",
            "impact_area": "Price|Quality|Marketing|Sales"
        }
    ],
    "closing_tip": "A short motivational or tactical tip for the agent"
}
EOT;
    }

    public function getOptions(): array
    {
        return [
            'temperature' => 0.6,
            'model' => 'gemini-1.5-pro', // Default for high-tier strategic analysis
        ];
    }
}
