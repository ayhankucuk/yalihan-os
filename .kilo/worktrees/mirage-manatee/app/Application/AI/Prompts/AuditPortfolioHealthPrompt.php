<?php

namespace App\Application\AI\Prompts;

use App\Domain\AI\Contracts\PromptInterface;

/**
 * 🛡️ AuditPortfolioHealthPrompt
 * Analytical audit of a portfolio of listings, identifying patterns and systemic issues.
 */
final class AuditPortfolioHealthPrompt implements PromptInterface
{
    private array $portfolioData;

    public function __construct(array $portfolioData)
    {
        $this->portfolioData = $portfolioData;
    }

    public function getSystemInstructions(): string
    {
        return <<<EOT
You are an AI Portfolio Auditor.
Your goal is to analyze a batch of real estate listings and identify systemic health issues or opportunities for improvement.
You must look for patterns in photo quality, pricing accuracy, and description completeness across the entire group.

Response must be in Turkish and valid JSON format.
EOT;
    }

    public function getUserPrompt(): string
    {
        $data = json_encode($this->portfolioData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return <<<EOT
Portfolio Data:
{$data}

Analyze this portfolio and provide a health audit report.
Output format:
{
    "health_score": 0-100,
    "critical_issues": [
        {
            "issue": "Description of the problem",
            "impact": "High|Medium|Low",
            "listings_affected": ["list of IDs or titles"]
        }
    ],
    "optimization_opportunities": ["Observation 1", "Observation 2"],
    "summary": "Overall assessment"
}
EOT;
    }

    public function getOptions(): array
    {
        return [
            'temperature' => 0.3, // Lower for more consistent analytical output
            'model' => 'deepseek-chat', // Optimized for analytical extraction/audit at scale
        ];
    }
}
