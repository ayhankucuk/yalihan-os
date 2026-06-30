<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Bekci\AuditMcpServer;
use Illuminate\Support\Facades\File;

/**
 * 🛡️ Yalıhan Bekçi MCP Audit Command
 * 
 * Context7 ihlallerini Telescope ve kod tabanından yakalar.
 * Otonom öğrenme sistemiyle çalışır.
 */
class BekciAuditCommand extends Command
{
    protected $signature = 'bekci:audit 
                            {--telescope : Sadece Telescope entry\'lerini tara}
                            {--code : Sadece kod dosyalarını tara}
                            {--report : Detaylı rapor oluştur}
                            {--since= : Telescope taraması için başlangıç zamanı (ISO8601)}';
    
    protected $description = 'MCP Audit Server - Context7 ihlallerini yakala ve öğren';
    
    protected AuditMcpServer $audit;
    
    public function __construct(AuditMcpServer $audit)
    {
        parent::__construct();
        $this->audit = $audit;
    }
    
    public function handle(): int
    {
        $this->info('🛡️  MCP AUDIT SERVER - Tarama başlatılıyor...');
        $this->newLine();
        
        $telescopeOnly = $this->option('telescope');
        $codeOnly = $this->option('code');
        $generateReport = $this->option('report');
        $since = $this->option('since');
        
        $violations = [];
        
        // Telescope taraması
        if ($telescopeOnly || (!$telescopeOnly && !$codeOnly)) {
            $this->info('📡 Telescope entry\'leri taranıyor...');
            
            $telescopeViolations = $this->audit->scanTelescopeEntries($since);
            
            $this->line(sprintf(
                '   Bulunan ihlal: <fg=yellow>%d</>',
                count($telescopeViolations)
            ));
            
            $violations['telescope'] = $telescopeViolations;
            
            if (count($telescopeViolations) > 0) {
                $this->displayViolations($telescopeViolations, 'Telescope');
            }
            
            $this->newLine();
        }
        
        // Kod taraması
        if ($codeOnly || (!$telescopeOnly && !$codeOnly)) {
            $this->info('📂 Kod dosyaları taranıyor...');
            
            $codeViolations = $this->audit->scanCodeFiles();
            
            $this->line(sprintf(
                '   Bulunan ihlal: <fg=yellow>%d</>',
                count($codeViolations)
            ));
            
            $violations['code'] = $codeViolations;
            
            if (count($codeViolations) > 0) {
                $this->displayViolations($codeViolations, 'Code');
            }
            
            $this->newLine();
        }
        
        // Rapor oluştur
        if ($generateReport) {
            $this->info('📊 Detaylı rapor oluşturuluyor...');
            
            $report = $this->audit->generateAuditReport();
            
            $reportPath = storage_path(sprintf(
                'logs/bekci-audit-report-%s.json',
                now()->format('Y-m-d-His')
            ));
            
            File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->line(sprintf('   Rapor: <fg=cyan>%s</>', $reportPath));
            $this->newLine();
        }
        
        // Özet
        $totalViolations = collect($violations)->flatten(1)->count();
        
        if ($totalViolations === 0) {
            $this->info('✅ Sistemde ihlal yok! (0 Violation)');
        } else {
            $this->warn(sprintf('⚠️  Toplam %d ihlal tespit edildi', $totalViolations));
        }
        
        return $totalViolations === 0 ? self::SUCCESS : self::FAILURE;
    }
    
    /**
     * İhlalleri tabloda göster
     */
    protected function displayViolations(array $violations, string $source): void
    {
        if (empty($violations)) {
            return;
        }
        
        $this->newLine();
        $this->line("   <fg=red>═══ {$source} İhlalleri ═══</>");
        
        $rows = [];
        $displayed = 0;
        
        foreach (array_slice($violations, 0, 10) as $violation) {
            $rows[] = [
                $violation['forbidden'] ?? '?',
                is_array($violation['sealed_alternative'] ?? null)
                    ? implode(', ', $violation['sealed_alternative'])
                    : ($violation['sealed_alternative'] ?? '?'),
                $this->truncate($violation['file'] ?? $violation['sql'] ?? '?', 50),
                $violation['line'] ?? $violation['timestamp'] ?? '?',
            ];
            $displayed++;
        }
        
        $this->table(
            ['Yasaklı', 'Mühürlü Alternatif', 'Konum', 'Satır/Zaman'],
            $rows
        );
        
        $remaining = count($violations) - $displayed;
        if ($remaining > 0) {
            $this->line(sprintf('   <fg=gray>... ve %d ihlal daha</>', $remaining));
        }
    }
    
    /**
     * Metni kısalt
     */
    protected function truncate(string $text, int $length = 50): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }
}
