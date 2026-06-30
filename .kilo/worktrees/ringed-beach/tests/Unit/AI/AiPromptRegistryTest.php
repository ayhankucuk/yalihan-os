<?php

namespace Tests\Unit\AI;

use App\Services\AI\Prompts\AiPromptRegistry;
use Tests\SimpleTestCase;

class AiPromptRegistryTest extends SimpleTestCase
{
    public function test_it_returns_correct_prompt_version()
    {
        $registry = new AiPromptRegistry();
        $prompt = $registry->get('listing_generation', 'v1');

        $this->assertStringContainsString('ACT AS A REAL ESTATE EXPERT', $prompt);
    }

    public function test_it_fails_on_missing_version()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $registry = new AiPromptRegistry();
        $registry->get('listing_generation', 'v999');
    }
}
