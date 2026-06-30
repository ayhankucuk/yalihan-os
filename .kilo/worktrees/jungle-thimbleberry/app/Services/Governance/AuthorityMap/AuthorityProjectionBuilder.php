<?php

namespace App\Services\Governance\AuthorityMap;

class AuthorityProjectionBuilder
{
    /**
     * Builds logical domain objects from the raw JSON payload.
     *
     * @param array $rawJson
     * @return array
     */
    public function build(array $rawJson): array
    {
        $domains = [];

        // 1. Context7 Standards
        if (isset($rawJson['context7_standards'])) {
            $domains[] = [
                'title' => 'Context7 Standartları ve Prensipler',
                'domain' => 'Mimari ve Kod Standartları (Context7)',
                'path' => '.sab/authority.json (`context7_standards`)',
                'role' => 'Projenin tek geçerli kod standartlarını, strict denetim seviyesini, ve zorunlu ilişkisel isimlendirme (`il_id`, `aktiflik_durumu` vb.) prensiplerini belirler.',
                'description' => 'Yalnızca Tailwind kullanımı zorunludur. Bootstrap veya neo-design-system gibi eski yapılar yasaklanmıştır.',
                'update_policy' => 'Yeni bir kural ekleneceğinde sadece ana otorite olan json dosyası güncellenir.',
                'overwrite_policy' => 'Bu doküman (md) read-only (sadece okunur) bir projeksiyondur.',
                'drift_note' => '`docs/context7-rules.md` gibi olası diğer belgelerle çelişki durumunda ana dosya referans alınacaktır.'
            ];
        }

        // 2. MCP ve IDE Ekosistemi
        if (isset($rawJson['ide_integrations']) || isset($rawJson['mcp_server_ecosystem'])) {
            $domains[] = [
                'title' => 'MCP ve IDE Ekosistemi',
                'domain' => 'Yapay Zeka Entegrasyonları ve Araçlar',
                'path' => '.sab/authority.json (`ide_integrations`, `mcp_server_ecosystem`)',
                'role' => 'Cursor, Windsurf, VSCode, Warp gibi IDE\'lerin sistemle (ve Bekçi MCP sunucuları ile) nasıl konuşacağını tanımlar.',
                'description' => '`yalihan-bekci-mcp` öğrenme ve fikir üretimi sağlarken, `context7-validator-mcp` kod doğrulama ve auto-fix uyarıları üretir.',
                'update_policy' => 'Port veya yol (path) değişiklikleri doğrudan JSON üzerinden yapılmalıdır.',
                'overwrite_policy' => 'İnsan tarafından bu projeksiyonda yapılacak güncellemeler geçersiz sayılır.',
                'drift_note' => 'Kullanıcı bilgisayarında koşan portlarla senkron tutulmalıdır.'
            ];
        }

        // 3. Komut Setleri
        if (isset($rawJson['laravel_commands'])) {
            $domains[] = [
                'title' => 'Komut Setleri ve Geliştirme İş Akışı',
                'domain' => 'Operasyon (CLI) ve Çalışma Ortamı',
                'path' => '.sab/authority.json (`laravel_commands`, `development_workflow`)',
                'role' => 'Kalite kapısı (Quality Gate), SAB (System Audit Bot) ve Bekçi süreçlerinin ana çalıştırma komutlarını tanımlar.',
                'description' => 'Tarama ve Onay: `php artisan sab:integrity-scan`, Guardian Kuralları vb.',
                'update_policy' => 'Artisan komutları eklendikçe json\'a işlenmeli, harita sonradan iz düşüm almalıdır.',
                'overwrite_policy' => 'Sistemin yegane execution (çalıştırma) arayüzüdür. Sabit kalır.',
                'drift_note' => 'Obsolete (kullanımdan kalkmış) komutlar varsa test senaryolarında uyarı verilecektir.'
            ];
        }

        // 4. Yönetişim
        if (isset($rawJson['governance'])) {
            $pipeline = $rawJson['governance']['ci_pipeline']['pipeline_name'] ?? 'Gold Line CI';
            $domains[] = [
                'title' => 'Yönetişim ve CI Doğrulama (Governance)',
                'domain' => 'CI/CD ve Üretim Kapısı',
                'path' => '.sab/authority.json (`governance`)',
                'role' => 'Sürekli entegrasyon hattı kural setini ve sürüm/dosya bayatlama (freshness) sınırlarını korur.',
                'description' => "Sistemin aktif tek CI pipeline'ı **{$pipeline}** olarak belirlenmiştir. 'new-only-fail' modeli devrededir.",
                'update_policy' => 'CI hattındaki bir Gate\'in atlanması ana json üzerinden yönetişim onayı gerektirir.',
                'overwrite_policy' => 'Pipeline isimlendirmeleri ve kuralları bu belge üzerinde bypass edilemez.',
                'drift_note' => 'Eski CI akışları kaldırılmış veya yerini Gold Line\'a bırakmıştır.'
            ];
        }

        return [
            'version' => $rawJson['version'] ?? 'unknown',
            'domains' => $domains
        ];
    }
}
