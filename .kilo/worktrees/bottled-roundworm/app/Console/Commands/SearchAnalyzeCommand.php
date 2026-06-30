<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\CortexNLPSearch;

class SearchAnalyzeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cortex:search {query : Natural language search query}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze natural language search query using Cortex NLP';

    /**
     * Execute the console command.
     */
    public function handle(CortexNLPSearch $nlp)
    {
        $query = $this->argument('query');

        $this->info("Cortex NLP Arama Motoru Başlatılıyor...");
        $this->line("Sorgu: '{$query}'");
        $this->newLine();

        $filters = $nlp->parseQuery($query);

        $this->info("🔍 YORUMLANAN NİYET (INTENT PARSING):");

        $this->table(
            ['Filtre Tipi', 'Değer'],
            [
                ['Kategori IDs', json_encode($filters['category_id'])],
                ['Özellikler (Features)', implode(', ', $filters['features'])],
                ['Oda Sayısı', $filters['room_count'] ?? '-'],
                ['Niyet (Intent)', $filters['intent']],
            ]
        );

        $this->newLine();
        $this->info("⏳ Veritabanı Sorgusu Hazırlanıyor...");

        // Mock execution or real execution
        // $results = $nlp->search($query);
        // $this->info("Bulunan Sonuç: " . $results->count());

        return 0;
    }
}
