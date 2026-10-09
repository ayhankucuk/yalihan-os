# AI Config Dosyaları Analiz Raporu

**Tarih:** 2026-10-09
**Konu:** 11 AI config dosyasının kullanım analizi

---

## 1. Kullanim Durumu

| Config Dosyasi | Kullanim | Durum |
|---------------|----------|--------|
| `ai-alerts.php` | AiAlertService | ✅ KULLANILIYOR |
| `ai-budgets.php` | AiBudgetGuard | ✅ KULLANILIYOR |
| `ai-cost-guard.php` | AiCostGuardService | ✅ KULLANILIYOR |
| `ai-governance.php` | WizardController, CircuitBreaker | ✅ KULLANILIYOR |
| `ai-prompts.php` | AiPromptRegistry | ✅ KULLANILIYOR |
| `ai-rate-limits.php` | ??? | ⚠️ KONTROL EDILMEDI |
| `ai-retention.php` | AiArchiveService, AiRetentionPolicyService | ✅ KULLANILIYOR |
| `ai-runtime.php` | AiRuntimeController, CircuitBreaker | ✅ KULLANILIYOR |
| `ai-storage.php` | ??? | ⚠️ KONTROL EDILMEDI |
| `ai.php` | LocalVisionService | ✅ KULLANILIYOR |
| `ai_mappings.php` | ??? | ⚠️ KONTROL EDILMEDI |

---

## 2. Kullanim Detaylari

### ai-alerts.php (12 kullanim)
```php
config('ai-alerts.enabled')
config('ai-alerts.channels.log|slack|email')
config('ai-alerts.cost_guard.*')
config('ai-alerts.provider_errors.*')
```

### ai-budgets.php (1 kullanim)
```php
config("ai-budgets.features.{$featureKey}.allow_admin_override")
```

### ai-cost-guard.php (5 kullanim)
```php
config('ai-cost-guard.enabled')
config('ai-cost-guard.budgets.daily.global_limit_usd')
config('ai-cost-guard.thresholds.kill_switch|downgrade')
```

### ai-governance.php (2 kullanim)
```php
config('ai-governance.adaptive_thresholds.suggest')
config('ai-runtime.circuit_breaker.*')
```

### ai-prompts.php (1 kullanim)
```php
config("ai-prompts.{$purpose}.{$version}")
```

### ai-retention.php (6 kullanim)
```php
config('ai-retention.archive.batch_size')
config('ai-retention.archive.verify_before_delete')
config("ai-retention.tables.{$tableName}.*")
config('ai-retention.default_retention_days')
```

### ai-runtime.php (2+ kullanim)
```php
config('ai-runtime.ai_enabled')
config('ai-runtime.circuit_breaker.*')
```

### ai.php (1 kullanim)
```php
config('ai.ollama_api_url')
```

---

## 3. Kullanim Potansiyeli Olanlar

### ai-rate-limits.php
Dokümantasyonda "roles" tanimlari var:
```php
'admin' => ['daily' => null, 'hourly' => null]
'broker' => ['daily' => 50, 'hourly' => 10]
'editor' => ['daily' => 20, 'hourly' => 5]
```

**Kontrol:** Rate limiter service'leri bu config'i kullanıyor mu?

### ai-storage.php
"local_mysql", "remote_mysql", "google_drive" ayarlari var.

**Kontrol:** Storage service'leri bu config'i kullanıyor mu?

### ai_mappings.php
Google Vision → Yalihan field mapping'leri var.

**Kontrol:** Vision service'leri bu config'i kullanıyor mu?

---

## 4. Birlesme Potansiyeli

### Dusuk - Dizin Yapisi Uygun

AI config'leri domain bazinda ayrilmis:
- `ai-alerts.php` — Alert sistemi
- `ai-budgets.php` — Budget yonetimi
- `ai-cost-guard.php` — Maliyet kontrolu
- `ai-governance.php` — Yonetissel ayarlar
- `ai-prompts.php` — Prompt sablonları
- `ai-retention.php` — Veri saklama
- `ai-runtime.php` — Calisma anı ayarları
- `ai-storage.php` — Depolama
- `ai.php` — Ollama ayarları
- `ai_mappings.php` — Vision mapping

### Neden Birleştirilmemis?
Her config farklı service tarafindan kullaniliyor:
- Ayri dosya = daha kolay bakim
- Deployment'ta ayri guncelleme
- Domain boundary'yi yansitiyor

### Birlesme Onerilir mi?
**HAYIR** — Mevcut yapı dogru. Domain bazinda ayrilik korunmali.

---

## 5. Oneriler

### Yapilacak Bir Sey Yok
- Tum config dosyalari aktif kullanimda
- Domain bazinda ayrilik mantikli
- Birlesme gereksiz kompleksite ekler

### Monitoring Ekleilebilir
Her config icin en az 1 kullanim oldugunu dogrulamak icin:
```bash
grep -r "config\('ai-" app/
```

---

## 6. Sonuc

| Config | Kullanim | Birlesme |
|--------|----------|----------|
| ai-alerts.php | ✅ | ❌ Gerek yok |
| ai-budgets.php | ✅ | ❌ Gerek yok |
| ai-cost-guard.php | ✅ | ❌ Gerek yok |
| ai-governance.php | ✅ | ❌ Gerek yok |
| ai-prompts.php | ✅ | ❌ Gerek yok |
| ai-rate-limits.php | ⚠️ TBD | ⚠️ Bakilabilir |
| ai-retention.php | ✅ | ❌ Gerek yok |
| ai-runtime.php | ✅ | ❌ Gerek yok |
| ai-storage.php | ⚠️ TBD | ⚠️ Bakilabilir |
| ai.php | ✅ | ❌ Gerek yok |
| ai_mappings.php | ⚠️ TBD | ⚠️ Bakilabilir |

**Sonuc:** 11 config dosyasinin cogu aktif kullanimda. Birlesme onermiyorum — domain bazinda ayrilik korunmali.
