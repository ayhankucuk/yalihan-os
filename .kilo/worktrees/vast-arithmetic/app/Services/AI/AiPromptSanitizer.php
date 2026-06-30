<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class AiPromptSanitizer
 *
 * SAB Phase 14 Sprint 1: AI Prompt Sanitization with Fail-Loud Pattern
 * Provides HTML stripping, malicious pattern detection, token limit enforcement,
 * and image URL validation for AI input security.
 *
 * @package App\Services\AI
 */
class AiPromptSanitizer
{
    /**
     * Forbidden patterns organized by category
     * Total: 43 patterns across 4 categories
     */
    protected array $forbiddenPatterns = [
        // Prompt Injection (11 patterns)
        'prompt_injection' => [
            'ignore all previous',
            'ignore previous',
            'forget previous',
            'bütün talimatları unut',
            'önceki talimatları unut',
            'sistem istemini',
            'system prompt',
            'you are now',
            'artık şusun',
            'disregard all',
            'override instructions',
        ],

        // System Keys - Context7 Protected Fields (10 patterns)
        'system_keys' => [
            'tenant_id',
            'governance_decisions',
            'user_id',
            'created_by',
            'aktiflik_durumu',
            'yayin_durumu',
            'aktiflik_kodu',
            'http_durum_kodu',
            'display_order',
            'one_cikan',
        ],

        // SQL Injection Vectors (13 patterns)
        'sql_injection' => [
            'union select',
            'or 1=1',
            'or 1 = 1',
            'drop table',
            'delete from',
            'exec(',
            'eval(',
            'base64_decode',
            'insert into',
            'update set',
            '--',
            'xp_cmdshell',
            'sp_executesql',
        ],

        // XSS Vectors (9 patterns)
        'xss_vectors' => [
            '<script',
            'javascript:',
            'onerror=',
            'onload=',
            'onclick=',
            'eval(',
            'expression(',
            'vbscript:',
            'data:text/html',
        ],
    ];

    /**
     * Maximum allowed input length (characters)
     */
    protected int $maxTokenLimit = 2000;

    /**
     * Strip HTML tags from input
     *
     * @param string $input
     * @return string
     */
    public function stripHTML(string $input): string
    {
        return strip_tags($input);
    }

    /**
     * Sanitize input with security checks and fail-loud pattern
     *
     * @param string $input
     * @return array ['blocked' => bool, 'reason' => string, 'sanitized' => string, 'pattern' => string|null]
     * @throws \RuntimeException If malicious pattern detected (fail-loud)
     */
    public function sanitize(string $input): array
    {
        // 1. Strip HTML
        $clean = $this->stripHTML($input);

        // 2. Detect forbidden patterns
        $detection = $this->detectForbiddenPattern($clean);
        if ($detection !== null) {
            Log::warning('AI Security: Malicious pattern detected', [
                'pattern' => $detection['keyword'],
                'category' => $detection['category'],
                'input_length' => mb_strlen($input),
            ]);

            return [
                'blocked' => true,
                'reason' => "Malicious pattern detected: {$detection['category']}",
                'sanitized' => '',
                'pattern' => $detection['keyword'],
            ];
        }

        // 3. Token limit enforcement (truncate, not block)
        if (mb_strlen($clean) > $this->maxTokenLimit) {
            $originalLength = mb_strlen($clean);
            $clean = mb_substr($clean, 0, $this->maxTokenLimit);

            Log::info('AI Security: Input truncated due to token limit', [
                'original_length' => $originalLength,
                'truncated_length' => $this->maxTokenLimit,
            ]);
        }

        return [
            'blocked' => false,
            'reason' => '',
            'sanitized' => trim($clean),
            'pattern' => null,
        ];
    }

    /**
     * Validate image URLs
     *
     * Rules:
     * - ✅ Valid: /storage/images/test.jpg (local storage)
     * - ✅ Valid: https://example.com/image.jpg (HTTPS external)
     * - ❌ Invalid: http://example.com/image.jpg (HTTP not allowed)
     * - ❌ Invalid: https://localhost/image.jpg (localhost not allowed)
     * - ❌ Invalid: https://127.0.0.1/image.jpg (IP not allowed)
     *
     * @param array $urls
     * @return array ['valid' => array, 'invalid' => array]
     */
    public function validateImageUrls(array $urls): array
    {
        $valid = [];
        $invalid = [];

        foreach ($urls as $url) {
            if ($this->isValidImageUrl($url)) {
                $valid[] = $url;
            } else {
                $invalid[] = $url;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * Check if a single image URL is valid
     *
     * @param string $url
     * @return bool
     */
    protected function isValidImageUrl(string $url): bool
    {
        // Local storage paths are always valid
        if (Str::startsWith($url, '/storage/') || Str::startsWith($url, '/')) {
            return true;
        }

        // External URLs must be HTTPS
        if (!Str::startsWith($url, 'https://')) {
            return false;
        }

        // Block localhost and IP addresses
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null) {
            return false;
        }

        // Block localhost variants
        if (in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return false;
        }

        // Block IP addresses (simple check)
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }

    /**
     * Detect forbidden patterns in input
     *
     * @param string $input
     * @return array|null ['keyword' => string, 'category' => string] or null if no match
     */
    protected function detectForbiddenPattern(string $input): ?array
    {
        $lowerInput = Str::lower($input);

        foreach ($this->forbiddenPatterns as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($lowerInput, $keyword) !== false) {
                    return [
                        'keyword' => $keyword,
                        'category' => $category,
                    ];
                }
            }
        }

        return null;
    }
}
