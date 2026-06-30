<?php

namespace App\Domain\AI\Enums;

/**
 * 🛡️ AI Provider Enum
 * Standard enumeration for supported AI transport layers.
 */
enum AIProvider: string
{
    case OLLAMA = 'ollama';
    case OPENAI = 'openai';
    case GEMINI = 'gemini';
    case DEEPSEEK = 'deepseek';
    case CLAUDE = 'claude';
}
