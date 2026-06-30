<?php

declare(strict_types=1);

namespace App\Console\Commands\Governance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Enums\Governance\GovernanceState;

/**
 * Bekçi Otonom Pattern Senkronizasyon Komutu (T-BEKCI — 2026-06-24)
 *
 * yalihan-bekci/knowledge/*.json dosyalarındaki öğrenme kayıtlarını
 * docs/governance/LEARNED_PATTERNS.json SSOT'una senkronize eder.
 *
 * Çalışma prensibi:
 *   1. Knowledge dizinindeki tüm learning_*.json dosyalarını tara
 *   2. rule_violated dolu veya context7_fix/naming_fix/security_fix tipindekilerini seç
 *   3. LEARNED_PATTERNS.json'daki mevcut pattern'lerle karşılaştır (signature bazlı)
 *   4. Yeni pattern'leri ekle, mevcut olanları atla (idempotent)
 *   5. Rapor yaz
 *
 * @see docs/known-debt.md §26
 */
class BekciPatternSyncCommand extends Command
{
    protected $signature = 'bekci:pattern:sync
        {--dry-run : Değişiklikleri uygulamadan göster}
        {--force : Mevcut pattern\'lerin context\'ini de güncelle}
        {--since= : Belirli tarihten itibaren (YYYY-MM-DD)}
        {--detail : Tüm işlem detaylarını göster}';

    protected $description = 'Bekçi knowledge dizinindeki öğrenmeleri LEARNED_PATTERNS.json SSOT\'una senkronize eder';

    /** LEARNED_PATTERNS.json yolu */
    private string $learnedPatternsPath;

    /** yalihan-bekci/knowledge/ dizini */
    private string $knowledgeDir;

    /** Hangi action_type'ları pattern olarak senkronize edilir */
    private array $syncableTypes = [
        'context7_fix',
        'naming_fix',
        'security_fix',
        'tenant_fix',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->learnedPatternsPath = base_path('docs/governance/LEARNED_PATTERNS.json');
        $this->knowledgeDir        = base_path('yalihan-bekci/knowledge');
    }

    public function handle(): int
    {
        $this->info('🔄 Bekçi Pattern Sync — Başlatılıyor...');
        $this->newLine();

        // 1. Dosyaları doğrula
        if (!File::isDirectory($this->knowledgeDir)) {
            $this->error("Knowledge dizini bulunamadı: {$this->knowledgeDir}");
            return self::FAILURE;
        }

        if (!File::exists($this->learnedPatternsPath)) {
            $this->error("LEARNED_PATTERNS.json bulunamadı: {$this->learnedPatternsPath}");
            return self::FAILURE;
        }

        // 2. Mevcut SSOT'u yükle
        $ssot = json_decode(File::get($this->learnedPatternsPath), true);
        if (!is_array($ssot) || !isset($ssot['patterns'])) {
            $this->error('LEARNED_PATTERNS.json geçersiz format.');
            return self::FAILURE;
        }

        // Mevcut signature setini hızlı lookup için indexle
        $existingSignatures = [];
        $existingContexts   = [];
        foreach ($ssot['patterns'] as $pattern) {
            if (!empty($pattern['signature'])) {
                $existingSignatures[$pattern['signature']] = $pattern['id'];
            }
            if (!empty($pattern['context_hash'])) {
                $existingContexts[$pattern['context_hash']] = $pattern['id'];
            }
        }

        // 3. Knowledge dosyalarını tara
        $since     = $this->option('since');
        $sinceDate = $since ? strtotime($since) : null;

        $knowledgeFiles = collect(File::files($this->knowledgeDir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.json'))
            ->sortBy(fn ($f) => $f->getMTime())
            ->values();

        $this->line("📂 " . $knowledgeFiles->count() . " knowledge dosyası bulundu.");

        $candidates = [];
        $skippedCount = 0;

        foreach ($knowledgeFiles as $file) {
            // Tarih filtresi
            if ($sinceDate && $file->getMTime() < $sinceDate) {
                $skippedCount++;
                continue;
            }

            $data = json_decode(File::get($file->getPathname()), true);
            if (!is_array($data)) {
                continue;
            }

            $actionType   = $data['action_type'] ?? '';
            $context      = $data['context'] ?? '';
            $ruleViolated = $data['rule_violated'] ?? null;

            // Syncable mi? (action_type veya rule_violated dolu olmalı)
            if (!in_array($actionType, $this->syncableTypes) && empty($ruleViolated)) {
                if ($this->option('detail')) {
                    $this->line("   ⏭ Atlandı ({$actionType}): " . $file->getFilename());
                }
                continue;
            }

            // Signature olarak context'in ilk 80 karakterini + dosya adını kullan
            $signature   = $this->buildSignature($data, $file->getFilename());
            $contextHash = md5($context);

            // Duplicate kontrolü
            if (isset($existingSignatures[$signature]) && !$this->option('force')) {
                if ($this->option('detail')) {
                    $this->line("   ✅ Mevcut ({$existingSignatures[$signature]}): " . $file->getFilename());
                }
                continue;
            }

            if (isset($existingContexts[$contextHash]) && !$this->option('force')) {
                if ($this->option('detail')) {
                    $this->line("   ✅ Context eşleşti: " . $file->getFilename());
                }
                continue;
            }

            $candidates[] = [
                'file'         => $file->getFilename(),
                'action_type'  => $actionType,
                'context'      => $context,
                'rule_violated' => $ruleViolated,
                'files_changed' => $data['files_changed'] ?? [],
                'timestamp'    => $data['timestamp'] ?? null,
                'signature'    => $signature,
                'context_hash' => $contextHash,
            ];
        }

        $this->newLine();
        $this->line("📊 Senkronize edilecek yeni pattern sayısı: <fg=yellow>" . count($candidates) . "</>");

        if (empty($candidates)) {
            $this->info('✅ Tüm pattern\'ler zaten senkronize. İşlem gerekmiyor.');
            return self::SUCCESS;
        }

        // 4. Yeni pattern'leri göster
        $this->newLine();
        $this->info('🆕 Yeni pattern\'ler:');
        foreach ($candidates as $c) {
            $ruleStr = $c['rule_violated'] ? " [{$c['rule_violated']}]" : '';
            $this->line("   + [{$c['action_type']}]{$ruleStr} — " . substr($c['context'], 0, 80) . '...');
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('⚠️  --dry-run modu: Değişiklikler uygulanmadı.');
            return self::SUCCESS;
        }

        // 5. SSOT'a ekle
        $addedCount  = 0;
        $nextId      = count($ssot['patterns']) + 1;

        foreach ($candidates as $c) {
            $patternId = 'LP-' . str_pad((string)($nextId++), 3, '0', STR_PAD_LEFT);

            $newPattern = [
                'id'            => $patternId,
                'name'          => $this->buildPatternName($c),
                'signature'     => $c['signature'],
                'context_hash'  => $c['context_hash'],
                'first_detected' => date('Y-m-d', $c['timestamp'] ? strtotime($c['timestamp']) : time()),
                'yayin_durumu'  => GovernanceState::ENFORCED->value, // Fixed: hardcoded string → enum
                'severity'      => $this->mapSeverity($c['action_type'], $c['rule_violated']),
                'action_type'   => $c['action_type'],
                'rule_violated' => $c['rule_violated'],
                'description'   => $c['context'],
                'files_changed' => $c['files_changed'],
                'source_file'   => $c['file'],
                'synced_at'     => date('Y-m-d'),
            ];

            $ssot['patterns'][] = $newPattern;
            $existingSignatures[$c['signature']] = $patternId;
            $addedCount++;

            $this->line("   ✅ Eklendi: [{$patternId}] " . $this->buildPatternName($c));
        }

        // 6. Meta verileri güncelle
        $ssot['total_learned'] = count($ssot['patterns']);
        $ssot['last_updated']  = date('Y-m-d');
        $ssot['last_sync']     = now()->toIso8601String();

        // 7. Yaz
        File::put(
            $this->learnedPatternsPath,
            json_encode($ssot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $this->newLine();
        $this->info("✅ Senkronizasyon tamamlandı. {$addedCount} yeni pattern eklendi.");
        $this->line("   SSOT: {$this->learnedPatternsPath}");
        $this->line("   Toplam pattern: " . $ssot['total_learned']);

        return self::SUCCESS;
    }

    /**
     * Knowledge kaydından pattern signature üret.
     * Context'in ilk 60 karakteri + action_type kombinasyonu.
     */
    private function buildSignature(array $data, string $filename): string
    {
        $context     = $data['context'] ?? '';
        $actionType  = $data['action_type'] ?? '';
        $ruleViolated = $data['rule_violated'] ?? '';

        // Kısa deterministik imza
        $base = $actionType . ':' . $ruleViolated . ':' . substr(trim($context), 0, 60);
        return 'bekci-sync:' . md5($base);
    }

    /**
     * Pattern için insan tarafından okunabilir isim üret.
     */
    private function buildPatternName(array $candidate): string
    {
        $actionType   = $candidate['action_type'];
        $ruleViolated = $candidate['rule_violated'];
        $context      = $candidate['context'];

        $prefix = match ($actionType) {
            'context7_fix' => 'Context7 Fix',
            'naming_fix'   => 'Naming Fix',
            'security_fix' => 'Security Fix',
            'tenant_fix'   => 'Tenant Isolation Fix',
            default        => 'Bekçi Learning',
        };

        $suffix = $ruleViolated ? " [{$ruleViolated}]" : '';

        // Context'ten kısa özet al
        $summary = substr(trim($context), 0, 50);
        $summary = preg_replace('/\s+/', ' ', $summary);

        return "{$prefix}{$suffix}: {$summary}";
    }

    /**
     * action_type ve rule_violated'dan severity belirle.
     */
    private function mapSeverity(string $actionType, ?string $ruleViolated): string
    {
        if ($actionType === 'security_fix' || $actionType === 'tenant_fix') {
            return 'CRITICAL';
        }

        if ($ruleViolated) {
            return match (true) {
                str_starts_with($ruleViolated, 'RULE-T') => 'CRITICAL', // Tenant rules
                str_starts_with($ruleViolated, 'RULE-S') => 'HIGH',     // Security rules
                str_starts_with($ruleViolated, 'RULE-N') => 'MEDIUM',   // Naming rules
                default => 'MEDIUM',
            };
        }

        return match ($actionType) {
            'context7_fix' => 'MEDIUM',
            'naming_fix'   => 'MEDIUM',
            default        => 'LOW',
        };
    }
}
