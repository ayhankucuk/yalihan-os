# Telemetry Map

> STATUS: REFERENCE ONLY — NOT SSOT
> Kural: unknown ≠ zero, no data ≠ healthy, stopped watcher = explicit warning

---

## Dashboard Telemetri Yüzeyleri

| Surface | URL | Ne Gösterdiğini İddia Eder | Gerçek Kaynak | Ne Zaman Healthy | False Healthy Riski |
|---------|-----|---------------------------|--------------|-----------------|-------------------|
| **AI Monitor** | `/admin/ai-monitor` | API latency, cost, provider status | Runtime probes + telemetry tables | Traffic + samples mevcut | 🔴 HIGH — 0 = veri yok olabilir |
| **AI Telemetry** | `/admin/ai/telemetry` | Maliyet, provider performans, hata analizi | `ai_logs`, `ai_prompt_logs` | Log kayıtları mevcut | 🔴 HIGH — provider down ama 0 hata |
| **AI İstatistikler** | `/admin/ai/statistics` | AI kullanım metrikleri | `ai_logs` (`olusturma_tarihi`) | Kayıt mevcut | 🟡 MEDIUM — boş tablo = 0 gösterir |
| **AI Governance** | `/admin/analytics/ai-governance` | Compliance, violation | Governance analytics tables | Proposal/decision traffic mevcut | 🔴 HIGH — traffic yoksa 100% compliance |
| **Governance Dashboard** | `/admin/governance` | Authority, audit, proposals | Governance tables + file parse | Pipeline + sync + audit geçerli | 🟡 MEDIUM — watcher durmuş olabilir |
| **Feature Health Matrix** | `/admin/governance/feature-health` | Assignment/template bütünlüğü | Feature/template tables | Assignment'lar doğru çözümlenmiş | 🟡 MEDIUM — scope mismatch gizli |
| **Cortex Analytics** | `/admin/cortex` | Cortex gelir/analiz | Cortex tables | Analiz verileri mevcut | 🟡 MEDIUM — veri yoksa boş |
| **İstatistikler** | `/admin/analitik/istatistikler` | Genel/İlan/Satış/Finans/Müşteri | İstatistik tables | Veri mevcut | 🟢 LOW — DB aggregation |
| **UPS Health** | `/admin/ups/health` | UPS sistem sağlığı | Runtime checks | Tüm kontroller geçer | 🟢 LOW — aktif kontrol |
| **Cache Stats** | `/monitoring/cache/stats` | Cache hit/miss oranları | Cache runtime | Cache aktif | 🟢 LOW — doğrudan ölçüm |
| **Health Monitor** | `/monitoring/health` | Sistem sağlık paneli | Runtime probes | Tüm probe'lar geçer | 🟢 LOW — doğrudan ölçüm |
| **Kanban Board** | `/admin/takim-yonetimi/board` | Görev durumları | `gorevler` table | Görev verileri mevcut | 🟢 LOW — DB-based |
| **CRM Pipeline** | `/admin/crm/pipeline` | Müşteri pipeline aşamaları | `kisiler` table | Kişi verileri mevcut | 🟢 LOW — DB-based |

---

## Required Semantics

```
unknown ≠ zero
  → Veri yoksa "bilinmiyor" yazılmalı, "0" değil

no data ≠ healthy
  → Veri yokluğu sağlık kanıtı değildir

stopped watcher = explicit warning
  → Durmuş watcher dashboard'da uyarı üretmeli
```

---

## AI Telemetri Tabloları

| Tablo | Ne Kaydeder | Yazılma Zamanı |
|-------|------------|---------------|
| `ai_logs` | Her AI isteği | İstek anında |
| `ai_prompt_logs` | Prompt geçmişi | Prompt gönderildiğinde |
| `ai_feature_usages` | Feature kullanım istatistikleri | Feature kullanıldığında |
| `ai_optimization_runs` | Optimizasyon çalışmaları | Optimizasyon çalıştığında |
| `ai_provider_decisions` | Provider seçim kararları | Her provider seçiminde |
| `ai_ogrenme_sinyalleri` | Öğrenme sinyalleri | Accept/reject aksiyonunda |
| `copilot_action_logs` | Copilot aksiyon logları | Copilot aksiyonunda |

---

## Governance Telemetri Tabloları

| Tablo | Ne Kaydeder | Yazılma Zamanı |
|-------|------------|---------------|
| `governance_decisions` | Karar kaydı | Karar üretildiğinde |
| `governance_audit_logs` | Denetim logu | Her aksiyon sonrası (append-only) |
| `governance_rollbacks` | Rollback kaydı | Rollback yapıldığında |
| `governance_suppressions` | Bastırma kaydı | Suppress yapıldığında |
| `governance_incidents` | Olay kaydı | Olay tespit edildiğinde |

---

## Doğrulama Kontrol Listesi

Bir telemetri yüzeyinin gerçekten çalıştığını doğrulamak için:

1. ✅ **Tabloda kayıt var mı?** — Son 24 saat içinde `COUNT(*)` > 0
2. ✅ **Provider gerçekten yanıt veriyor mu?** — AI settings'ten test endpoint'i çalıştır
3. ✅ **Watcher process çalışıyor mu?** — `ps aux | grep sab-watch`
4. ✅ **Queue worker çalışıyor mu?** — `php artisan horizon:status` veya `queue:monitor`
5. ✅ **Governance pipeline durumu** — `php artisan system:env-drift-guard`
