<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use Illuminate\Support\Facades\Http;

/**
 * RetrievalService - loads local JSONL index and provides top-k retrieval.
 */
class RetrievalService
{
    protected $index = [];
    protected $lexIndex = [];
    protected $embeddingDim = 0;
    protected $indexPath;
    protected $lexPath;

    public function __construct($vectorClient = null)
    {
        // vectorClient optional for future use; not required for local JSONL index
        $this->vectorClient = $vectorClient;
        $this->indexPath = storage_path('ai/kb/index.jsonl');
        $this->lexPath = storage_path('ai/kb/index.lex.json');
    }

    /**
     * Load index.jsonl into memory.
     */
    public function loadIndex(): void
    {
        $this->index = [];
        if (! file_exists($this->indexPath)) {
            return;
        }
        $fh = fopen($this->indexPath, 'r');
        if (! $fh) {
            return;
        }
        while (($line = fgets($fh)) !== false) {
            $obj = json_decode(trim($line), true);
            if (! $obj) {
                continue;
            }
            $this->index[] = $obj;
        }
        fclose($fh);
        if (! empty($this->index) && isset($this->index[0]['embedding'])) {
            $this->embeddingDim = count($this->index[0]['embedding']);
        }
        if (file_exists($this->lexPath)) {
            $lexData = file_get_contents($this->lexPath);
            $this->lexIndex = json_decode($lexData, true) ?: [];
        }
    }

    /**
     * Embed a query string. Uses OpenAI if OPENAI_API_KEY present, otherwise fallback deterministic hash.
     */
    public function embedQuery(string $q): array
    {
        $openaiKey = config('ai.api_key') ?: null;
        if (! empty($openaiKey) && class_exists(Http::class)) {
            try {
                $resp = Http::withToken($openaiKey)
                    ->post('https://api.openai.com/v1/embeddings', [
                        'model' => 'text-embedding-3-small',
                        'input' => $q,
                    ]);
                if ($resp->successful()) {
                    $json = $resp->json();
                    if (isset($json['data'][0]['embedding'])) {
                        return $json['data'][0]['embedding'];
                    }
                }
            } catch (\Exception $e) {
                // fallback to hash-based
            }
        }
        // Fallback deterministic embedding (256 dims)
        return $this->hashEmbedding($q, 256);
    }

    protected function hashEmbedding(string $text, int $dim = 256): array
    {
        $vec = [];
        for ($i = 0; $i < $dim; $i++) {
            $h = hash('sha256', $text . '|' . $i);
            $part = substr($h, 0, 8);
            $val = (hexdec($part) / 4294967295.0) * 2 - 1; // normalize to [-1,1]
            $vec[] = $val;
        }
        return $vec;
    }

    protected function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }
        if ($na == 0 || $nb == 0) {
            return 0.0;
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * Search top-k by query string.
     * Hybrid: lexical (70%) + embedding (30%) if OPENAI_API_KEY exists, else 100% lexical.
     * Returns array of ['id','score','lexical_score','embedding_score','metadata','text']
     */
    public function search(string $q, int $k = 5): array
    {
        if (empty($this->index)) {
            $this->loadIndex();
        }
        if (empty($this->index)) {
            return [];
        }
        $qLower = mb_strtolower($q, 'UTF-8');
        $qTokens = $this->tokenize($qLower);
        $useEmbedding = !empty(config('ai.api_key'));
        $qemb = $useEmbedding ? $this->embedQuery($q) : [];

        $results = [];
        foreach ($this->index as $idx => $item) {
            $lexScore = $this->lexicalScore($qTokens, $item, $idx);
            $embScore = 0.0;
            if ($useEmbedding && isset($item['embedding'])) {
                $embScore = $this->cosineSimilarity($qemb, $item['embedding']);
            }
            $finalScore = $useEmbedding ? ($lexScore * 0.7 + $embScore * 0.3) : $lexScore;
            $results[] = [
                'id' => $item['id'] ?? ($item['metadata']['id'] ?? null),
                'score' => $finalScore,
                'lexical_score' => $lexScore,
                'embedding_score' => $embScore,
                'metadata' => $item['metadata'] ?? [],
                'text' => $item['text'] ?? '',
            ];
        }
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        return array_slice($results, 0, $k);
    }

    protected function tokenize(string $text): array
    {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $stopwords = ['ve', 'veya', 'ile', 'da', 'de', 'mi', 'mu', 'mı', 'mü'];
        return array_filter($tokens, fn($t) => !in_array($t, $stopwords));
    }

    protected function lexicalScore(array $qTokens, array $item, int $idx): float
    {
        $meta = $item['metadata'] ?? [];
        $lexItem = $this->lexIndex[$idx] ?? null;
        $docTokens = $lexItem ? $lexItem['tokens'] : $this->tokenize(mb_strtolower($item['text'] ?? '', 'UTF-8'));

        $weights = [
            'kategori' => 3.0,
            'alt_kategori' => 3.0,
            'yayin_tipi' => 2.5,
            'baslik' => 2.0,
            'il' => 1.5,
            'ilce' => 1.5,
        ];

        $score = 0.0;
        foreach ($weights as $field => $w) {
            if (isset($meta[$field])) {
                $fieldTokens = $this->tokenize(mb_strtolower((string)$meta[$field], 'UTF-8'));
                $matches = count(array_intersect($qTokens, $fieldTokens));
                $score += $matches * $w;
            }
        }
        $textMatches = count(array_intersect($qTokens, $docTokens));
        $score += $textMatches * 1.0;

        $maxScore = count($qTokens) * 3.0;
        return $maxScore > 0 ? min($score / $maxScore, 1.0) : 0.0;
    }

    /**
     * Retrieve top-k contexts concatenated as string.
     */
    public function retrieveContext(string $q, int $k = 5): string
    {
        $top = $this->search($q, $k);
        $parts = array_map(function ($r) {
            return $r['text'];
        }, $top);
        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Simple PII filter
     */
    public function filterPII(string $text): string
    {
        $text = preg_replace('/\b\d{11}\b/', '[REDACTED]', $text);
        $text = preg_replace('/\b\+?\d{10,15}\b/', '[REDACTED]', $text);
        return $text;
    }
}
