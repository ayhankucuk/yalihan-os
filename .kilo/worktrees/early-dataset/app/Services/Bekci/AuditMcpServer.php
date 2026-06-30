<?php

namespace App\Services\Bekci;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 🛡️ YalıhanAI MCP Audit Server
 * 
 * Telescope entegrasyonlu otonom kod denetim ve öğrenme sistemi.
 * Context7 ihlallerini yakalayıp Bekçi knowledge base'e mühürler.
 * 
 * @version 2.0.0
 * @context7-autonomous-protocol
 */
class AuditMcpServer
{
    protected string $knowledgeBasePath;
    protected string $learningPath;
    protected array $forbiddenPatterns;
    protected array $sealedColumns;
    
    public function __construct()
    {
        $this->knowledgeBasePath = base_path('yalihan-bekci/knowledge');
        $this->learningPath = base_path('yalihan-bekci/learning');
        
        // Context7 Authority'den yasaklı pattern'leri yükle
        $this->loadAuthorityRules();
    }
    
    /**
     * Authority.json'dan mühürlü standartları yükle
     */
    protected function loadAuthorityRules(): void
    {
        $authorityPath = base_path('.sab/authority.json');

        if (!File::exists($authorityPath)) {
            throw new \RuntimeException(
                '[AuditMcpServer] Authority SSOT bulunamadı: ' . $authorityPath .
                ' — Governance engine başlatılamaz.'
            );
        }

        $authority = json_decode(File::get($authorityPath), true);

        // .sab/authority.json canonical field mapping
        $forbiddenFields = $authority['governance']['forbidden_fields'] ?? [];
        $canonicalMap    = $authority['governance']['canonical'] ?? [];

        // Build forbidden_patterns from .sab structure
        $this->forbiddenPatterns = [];
        foreach ($forbiddenFields as $field) {
            $this->forbiddenPatterns[$field] = isset($canonicalMap[$field])
                ? [$canonicalMap[$field]]
                : ['UNKNOWN — check .sab/authority.json canonical map'];
        }

        // sealed_columns from table-specific guards
        $this->sealedColumns = [];
        $tableGuards = $authority['governance']['table_specific_guards'] ?? [];
        foreach ($tableGuards as $table => $guard) {
            foreach ($guard['canonical'] ?? [] as $canonical) {
                $this->sealedColumns[] = $canonical;
            }
        }
        $this->sealedColumns = array_unique($this->sealedColumns);
    }
    
    /**
     * 🔍 Telescope Entry'lerini izle ve ihlalleri yakala
     * 
     * @param string|null $since Son tarama zamanı (ISO8601)
     * @return array Yakalanan ihlaller
     */
    public function scanTelescopeEntries(?string $since = null): array
    {
        $violations = [];
        
        // Telescope'un mevcut olup olmadığını kontrol et
        if (!class_exists(\Laravel\Telescope\Telescope::class)) {
            Log::channel('bekci')->info('MCP Audit: Telescope kurulu değil, atlıyorum');
            return [];
        }
        
        // Telescope entries'in var olup olmadığını kontrol et
        try {
            DB::table('telescope_entries')->limit(1)->get();
        } catch (\Exception $e) {
            Log::channel('bekci')->warning('MCP Audit: Telescope entries tablosu bulunamadı', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
        
        // Telescope queries tablosunu tara
        $queries = DB::table('telescope_entries')
            ->where('type', 'query') // context7-ignore
            ->when($since, function ($q) use ($since) {
                $q->where('created_at', '>', $since);
            })
            ->orderBy('created_at', 'desc') // context7-ignore
            ->limit(1000)
            ->get();
        
        foreach ($queries as $entry) {
            $content = json_decode($entry->content, true);
            $sql = $content['sql'] ?? '';
            $bindings = $content['bindings'] ?? [];
            
            // Yasaklı pattern kontrolü
            foreach ($this->forbiddenPatterns as $forbidden => $sealed) {
                if (stripos($sql, $forbidden) !== false) {
                    $violations[] = [
                        'type' => 'forbidden_column', // context7-ignore
                        'forbidden' => $forbidden,
                        'sealed_alternative' => $sealed,
                        'sql' => $sql,
                        'bindings' => $bindings,
                        'timestamp' => $entry->created_at,
                        'uuid' => $entry->uuid,
                    ];
                }
            }
        }
        
        // Telescope exceptions tablosunu tara
        $exceptions = DB::table('telescope_entries')
            ->where('type', 'exception') // context7-ignore
            ->when($since, function ($q) use ($since) {
                $q->where('created_at', '>', $since);
            })
            ->orderBy('created_at', 'desc') // context7-ignore
            ->limit(500)
            ->get();
        
        foreach ($exceptions as $entry) {
            $content = json_decode($entry->content, true);
            $message = $content['message'] ?? '';
            $file = $content['file'] ?? '';
            $line = $content['line'] ?? 0;
            
            // "Column not found" hatalarını yakala
            if (stripos($message, 'column') !== false || stripos($message, 'unknown column') !== false) {
                foreach ($this->forbiddenPatterns as $forbidden => $sealed) {
                    if (stripos($message, $forbidden) !== false) {
                        $violations[] = [
                            'type' => 'column_not_found', // context7-ignore
                            'forbidden' => $forbidden,
                            'sealed_alternative' => $sealed,
                            'message' => $message,
                            'file' => $file,
                            'line' => $line,
                            'timestamp' => $entry->created_at,
                            'uuid' => $entry->uuid,
                        ];
                    }
                }
            }
        }
        
        // Öğrenme sistemine kaydet
        if (!empty($violations)) {
            $this->learnFromViolations($violations);
        }
        
        return $violations;
    }
    
    /**
     * 📚 İhlallerden öğren ve knowledge base'e kaydet
     */
    protected function learnFromViolations(array $violations): void
    {
        $learningFile = sprintf(
            '%s/telescope-violations-%s.json',
            $this->learningPath,
            Carbon::now()->format('Y-m-d')
        );
        
        // Bugünün öğrenme dosyası varsa yükle
        $existingLearning = [];
        if (File::exists($learningFile)) {
            $existingLearning = json_decode(File::get($learningFile), true) ?? [];
        }
        
        // Yeni ihlalleri ekle
        $learning = [
            'date' => Carbon::now()->toIso8601String(),
            'total_violations' => count($violations),
            'violations' => array_merge($existingLearning['violations'] ?? [], $violations),
            'patterns_detected' => $this->extractPatterns($violations),
            'auto_fix_suggestions' => $this->generateAutoFixSuggestions($violations),
        ];
        
        // Dosyaya kaydet
        File::ensureDirectoryExists(dirname($learningFile));
        File::put($learningFile, json_encode($learning, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        Log::channel('bekci')->info('MCP Audit: Yeni ihlaller öğrenildi', [
            'count' => count($violations),
            'file' => $learningFile,
        ]);
    }
    
    /**
     * 🧠 İhlal pattern'lerini çıkar
     */
    protected function extractPatterns(array $violations): array
    {
        $patterns = [];
        
        foreach ($violations as $violation) {
            $key = $violation['forbidden'] ?? 'unknown';
            
            if (!isset($patterns[$key])) {
                $patterns[$key] = [
                    'forbidden' => $key,
                    'sealed_alternatives' => is_array($violation['sealed_alternative'] ?? null) 
                        ? $violation['sealed_alternative'] 
                        : [$violation['sealed_alternative'] ?? 'unknown'],
                    'occurrences' => 0,
                    'files' => [],
                ];
            }
            
            $patterns[$key]['occurrences']++;
            
            if (isset($violation['file'])) {
                $patterns[$key]['files'][] = [
                    'path' => $violation['file'],
                    'line' => $violation['line'] ?? 0,
                ];
            }
        }
        
        return array_values($patterns);
    }
    
    /**
     * 🔧 Otomatik düzeltme önerileri oluştur
     */
    protected function generateAutoFixSuggestions(array $violations): array
    {
        $suggestions = [];
        
        foreach ($violations as $violation) {
            $forbidden = $violation['forbidden'] ?? '';
            $sealed = $violation['sealed_alternative'] ?? [];
            
            if (!is_array($sealed)) {
                $sealed = [$sealed];
            }
            
            $file = $violation['file'] ?? null;
            
            if ($file && File::exists(base_path($file))) {
                $suggestions[] = [
                    'file' => $file,
                    'line' => $violation['line'] ?? 0,
                    'search' => $forbidden,
                    'replace_with' => $sealed[0] ?? 'unknown',
                    'command' => sprintf(
                        'sed -i "" "s/%s/%s/g" %s',
                        $forbidden,
                        $sealed[0] ?? 'unknown',
                        $file
                    ),
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * 🛡️ Kod dosyalarını direkt tara (Telescope'suz)
     * 
     * @param array $paths Taranacak dizinler
     * @return array Bulunan ihlaller
     */
    public function scanCodeFiles(array $paths = []): array
    {
        if (empty($paths)) {
            $paths = [
                app_path('Models'),
                app_path('Http/Controllers'),
                resource_path('views'),
            ];
        }
        
        $violations = [];
        
        foreach ($paths as $path) {
            if (!File::isDirectory($path)) {
                continue;
            }
            
            $files = File::allFiles($path);
            
            foreach ($files as $file) {
                $content = File::get($file->getPathname());
                $lines = explode("\n", $content);
                
                foreach ($lines as $lineNumber => $line) {
                    foreach ($this->forbiddenPatterns as $forbidden => $sealed) {
                        // Accessor/method tanımlarını atla
                        if (preg_match('/function\s+get.*Attribute/', $line)) {
                            continue;
                        }
                        
                        // Yorum satırlarını atla
                        if (preg_match('/^\s*\/\//', trim($line)) || preg_match('/^\s*\*/', trim($line))) {
                            continue;
                        }
                        
                        // Yasaklı kelimeyi ara
                        if (preg_match("/['\"]?{$forbidden}['\"]?/i", $line)) {
                            $violations[] = [
                                'type' => 'forbidden_pattern', // context7-ignore
                                'file' => str_replace(base_path().'/', '', $file->getPathname()),
                                'line' => $lineNumber + 1,
                                'content' => trim($line),
                                'forbidden' => $forbidden,
                                'sealed_alternative' => $sealed,
                                'timestamp' => Carbon::now()->toIso8601String(),
                            ];
                        }
                    }
                }
            }
        }
        
        return $violations;
    }
    
    /**
     * 📊 Audit raporu oluştur
     */
    public function generateAuditReport(): array
    {
        $telescopeViolations = $this->scanTelescopeEntries(Carbon::now()->subHours(24)->toIso8601String());
        $codeViolations = $this->scanCodeFiles();
        
        $report = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'telescope_violations' => count($telescopeViolations),
            'code_violations' => count($codeViolations),
            'total_violations' => count($telescopeViolations) + count($codeViolations),
            'forbidden_patterns' => $this->forbiddenPatterns,
            'sealed_columns' => $this->sealedColumns,
            'violations' => [
                'telescope' => $telescopeViolations,
                'code' => $codeViolations,
            ],
        ];
        
        // Raporu kaydet
        $reportFile = storage_path(sprintf(
            'logs/bekci-audit-report-%s.json',
            Carbon::now()->format('Y-m-d-His')
        ));
        
        File::put($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $report;
    }
    
    /**
     * 🔄 Otonom tarama başlat (cron/scheduler için)
     */
    public function autonomousScan(): void
    {
        Log::channel('bekci')->info('MCP Audit: Otonom tarama başlatıldı');
        
        $report = $this->generateAuditReport();
        
        if ($report['total_violations'] > 0) {
            Log::channel('bekci')->warning('MCP Audit: İhlaller tespit edildi', [
                'telescope' => $report['telescope_violations'],
                'code' => $report['code_violations'],
                'total' => $report['total_violations'],
            ]);
        } else {
            Log::channel('bekci')->info('MCP Audit: Sistemde ihlal yok (0 Violation) ✅');
        }
    }
}
