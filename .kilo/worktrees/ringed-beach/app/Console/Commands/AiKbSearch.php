<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\RetrievalService;

class AiKbSearch extends Command
{
    protected $signature = 'ai:kb-search {query} {--k=5}';
    protected $description = 'Search local AI KB index and return top-k matches';

    public function handle()
    {
        $query = $this->argument('query');
        $k = (int)$this->option('k');

        $rs = new RetrievalService();
        $rs->loadIndex();
        $results = $rs->search($query, $k);
        if (empty($results)) {
            $this->error('No index found or no results. Ensure storage/ai/kb/index.jsonl exists and is populated.');
            return 1;
        }

        foreach ($results as $r) {
            $meta = $r['metadata'] ?? [];
            $kat = $meta['kategori'] ?? '-';
            $altKat = $meta['alt_kategori'] ?? '-';
            $yayinTipi = $meta['yayin_tipi'] ?? '-';
            $lexScore = $r['lexical_score'] ?? 0;
            $embScore = $r['embedding_score'] ?? 0;
            $this->line(sprintf(
                '🎯 score=%.4f (lex=%.4f emb=%.4f) | id=%s | %s',
                $r['score'],
                $lexScore,
                $embScore,
                $r['id'] ?? '-',
                $meta['baslik'] ?? '-'
            ));
            $this->line(sprintf(
                '   📍 %s/%s | 🏠 %s > %s | 💰 %s | 📢 %s',
                $meta['il'] ?? '-',
                $meta['ilce'] ?? '-',
                $kat,
                $altKat,
                number_format($meta['fiyat'] ?? 0) . ' TL',
                $yayinTipi
            ));
            $this->line('');
        }

        // Guard expectations (info only)
        $qLower = mb_strtolower($query, 'UTF-8');
        $top3 = array_slice($results, 0, 3);

        if (str_contains($qLower, 'arsa')) {
            $hasArsa = false;
            foreach ($top3 as $r) {
                $m = $r['metadata'] ?? [];
                if (($m['kategori'] ?? '') === 'arsa_arazi' || ($m['alt_kategori'] ?? '') === 'arsa') {
                    $hasArsa = true;
                    break;
                }
            }
            if ($hasArsa) {
                $this->info('✅ Guard: Query contains "arsa" and top-3 includes arsa_arazi/arsa');
            } else {
                $this->info('⚠️  Guard: Query contains "arsa" but top-3 does NOT include arsa_arazi/arsa');
            }
        }

        if (str_contains($qLower, 'daire')) {
            $hasDaire = false;
            foreach ($top3 as $r) {
                $m = $r['metadata'] ?? [];
                if (($m['kategori'] ?? '') === 'konut' || ($m['alt_kategori'] ?? '') === 'daire') {
                    $hasDaire = true;
                    break;
                }
            }
            if ($hasDaire) {
                $this->info('✅ Guard: Query contains "daire" and top-3 includes konut/daire');
            } else {
                $this->info('⚠️  Guard: Query contains "daire" but top-3 does NOT include konut/daire');
            }
        }

        return 0;
    }
}
