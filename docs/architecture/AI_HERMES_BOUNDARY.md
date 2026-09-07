# YALIHAN OS — AI / HERMES BOUNDARY

**Tarih:** 2026-09-07
**Durum:** DOCUMENTED / VALIDATION_PENDING
**Kaynak:** ARCHITECTURE_BACKBONE_AUDIT.md §8, Constitution Madde 11-12, SAB.md Rule 17, REGISTRY.md §5

---

## 1. Amaç

Bu belge, Yalıhan OS'de AI (Cortex) ve Hermes (Orchestration) katmanlarının:
- Sorumluluk sınırlarını
- Domain ile ilişkilerini
- Tenant context gereksinimlerini
- Güvenlik sınırlarını
- AI ajan yetki matrixini

tanımlar.

---

## 2. AI / Cortex Sınırı (Constitution Madde 11)

### 2.1 Cortex Provider Abstraction

```
Kural: Tüm AI çağrıları CortexProviderInterface üzerinden (Madde 11.2)

interface CortexProviderInterface {
    public function generate(CortexRequestDTO $request): CortexResponseDTO;
    public function analyze(CortexRequestDTO $request): CortexResponseDTO;
    public function embed(CortexRequestDTO $request): CortexResponseDTO;
}

Mevcut Adaptörler:
  - OllamaDriver (yerel)
  - OpenAiDriver
  - DeepSeekDriver
  - ClaudeDriver
  - FakeCortexDriver (dev/test)
```

### 2.2 AI Çıktı Sözleşmesi

```
Kural: AI çıktısı şemalı ve tip korumalı DTO'ya dönüştürülmeli (Madde 11.2)

CortexResponseDTO:
  - content: string (sanitize edilmiş)
  - tokens_used: int
  - latency_ms: int
  - model_version: string
  - confidence: float|null

Yasak:
  - AI çıktısını doğrudan DB'ye yazmak (Madde 11.3)
  - JSON doğrulaması geçmeden AI çıktısını kullanmak
  - Prompt'ları PHP sınıflarına hardcoded yazmak (Madde 11.3)
```

### 2.3 AI Budget Guard (SAB.md Rule 17)

```
Kural: Her AI operasyonu AiBudgetGuard kontrolüne tabi

AiBudgetGuard::canExecute():
  - Tenant bazlı bütçe kontrolü
  - Günlük/aylık limit kontrolü
  - Maliyet takibi

Yasak (SAB.md Mali Suçlar §4):
  - AiBudgetGuard::canExecute() kontrolü olmadan AI servisi çalıştırmak
```

### 2.4 AI Ajan Yetki Sınırları (Constitution Madde 15.2.2)

```
Kural: Hiçbir AI ajanı SuperAdmin yetkisine sahip olamaz
İlke: Least Privilege

REGISTRY.md §5 — AI Agent Roles:
  | Agent | Rol | İzin Verilen Alanlar | Yasaklar |
  |-------|-----|---------------------|----------|
  | Antigravity | Mimari, UI/UX, Güvenlik | Docs, Architecture, Tests, Frontend | DB destructive, prod push |
  | Kilo | Core Engineering | Domain Services, Controllers, Migrations | SAB ihlali |
  | Codex | Code Review | PR review, Pint, PHPStan | İş mantığı tek taraflı değişiklik |
  | Wenox | Operasyon & Test | CI/CD, E2E, Health check | Yetkisiz branch merge |
```

---

## 3. Hermes Sınırı (Constitution Madde 12)

### 3.1 Hermes Pure Orchestrator Policy (ADR-005)

```
Hermes = Pure Orchestrator

Sorumlulukları:
  ✅ Job dispatch
  ✅ Retry politikası
  ✅ Timeout denetimi
  ✅ Rate limiting
  ✅ Cron scheduling
  ✅ Idempotency kontrolü

YASAK (Constitution Madde 12.3):
  ❌ İş mantığı yazmak
  ❌ "Bu villa lüks ise fiyatı %10 artır"
  ❌ "İlanı sahibinden portalına yükle"
  ❌ Fiyat hesaplama
  ❌ Müşteri eşleştirme
  ❌ Sonsuz döngü riski taşıyan job tanımlamak
```

### 3.2 Hermes Event Log

| Alan | Durum | Kanıt |
|------|-------|-------|
| `tenant_id` | 🔴 Leakage — bazı event'lerde tenant_id yok | REPO_VERIFIED (SECURITY_EVIDENCE §3) |
| `idempotency_key` | DOCUMENTED — zorunlu (Madde 12.2) | DOCUMENTED |
| `event_type` | ✅ | REPO_VERIFIED |
| `payload` | ✅ | REPO_VERIFIED |
| `status` | ✅ | REPO_VERIFIED |
| `retry_count` | ✅ | REPO_VERIFIED |

**Gereken:** Hermes event log'a tenant_id eklenmeli, leakage düzeltilmeli.

### 3.3 Hermes Workforce (known-debt #39)

```
Durum: ✅ ÇÖZÜLDÜ (2026-09-06)
Kanıt: docs/known-debt.md satır 276-284
```

---

## 4. AI / Domain İlişkisi

### 4.1 AI Domain Bağımlılıkları

```
AI/Cortex Domain:
  → CortexProviderInterface (AI sağlayıcı soyutlaması)
  → AiBudgetGuard (maliyet kontrolü)
  → Prompt Registry (versiyonlu prompt şablonları)
  → CortexResponseDTO (tip güvenli çıktı)

Bağımlılık İzni:
  AI → CortexProviderInterface (Constitution Madde 11.2)
  AI → Listing (Contract ile — ilan metni üretimi)
  AI → Media (Contract ile — görsel analiz)
  AI → Property (Contract ile — değerleme analizi)
```

### 4.2 AI Servisleri

| Servis | Görev | Tenant Context | Kanıt |
|--------|-------|----------------|-------|
| İlan metin üretimi | Listing → AI → ListingPublished | 🔴 Değerlendir | REPO_VERIFIED |
| Görsel etiketleme | Media → AI → MediaVariantsReady | 🔴 Değerlendir | REPO_VERIFIED |
| Değerleme analizi | Property → AI → CortexResponse | 🔴 Değerlendir | REPO_VERIFIED |
| Müşteri eşleştirme | CRM → AI → MatchResult | 🔴 Değerlendir | REPO_VERIFIED |
| Fiyat önerisi | Listing → AI → PriceSuggestion | 🔴 Değerlendir | REPO_VERIFIED |

> Tüm AI servisleri tenant-aware olmalı — AI çıktısı tenant boundary'yi aşamaz.

---

## 5. Güvenlik Sınırları

### 5.1 AI Ajan Güvenlik Kuralları

| Kural | Kaynak | Durum |
|-------|--------|-------|
| AI ajanlar SuperAdmin olamaz | Constitution Madde 15.2.2 | DOCUMENTED |
| AI ajanlar Least Privilege ile sınırlandırılır | Constitution Madde 15.2.2 | DOCUMENTED |
| AI çıktısı sanitize edilir | Constitution Madde 11.3 | DOCUMENTED |
| AI maliyeti takip edilir | SAB.md Rule 17 | DOCUMENTED |
| AI prompt'ları versiyonlu registry'de | Constitution Madde 11.3 | DOCUMENTED |

### 5.2 Hermes Güvenlik Kuralları

| Kural | Kaynak | Durum |
|-------|--------|-------|
| Hermes event'leri idempotency_key taşır | Constitution Madde 12.2 | DOCUMENTED |
| Hermes event log tenant_id taşır | Constitution Madde 13 | 🔴 Gap |
| Hermes'te iş mantığı yok | Constitution Madde 12.3 | DOCUMENTED |
| Hermes retry limiti var | Constitution Madde 12.3 | DOCUMENTED |
| Hermes timeout var | Constitution Madde 12.3 | DOCUMENTED |

---

## 6. Kabul Kriterleri

### 6.1 AI/Cortex

- [ ] Tüm AI çağrıları CortexProviderInterface üzerinden
- [ ] AI çıktısı CortexResponseDTO'ya dönüştürülüyor
- [ ] AiBudgetGuard her AI operasyonunda kontrol ediliyor
- [ ] AI ajanlar SuperAdmin yetkisine sahip değil
- [ ] Prompt'lar versiyonlu registry'de
- [ ] AI servisleri tenant-aware

### 6.2 Hermes

- [ ] Hermes pure orchestrator (iş mantığı yok)
- [ ] Hermes event log tenant_id taşır
- [ ] Hermes event log idempotency_key taşır
- [ ] Hermes retry limiti ve timeout var
- [ ] Hermes workforce runtime wiring tam

---

## 7. Test Gereksinimleri

| Test | Açıklama | Öncelik |
|------|---------|---------|
| Provider abstraction test | Tüm driver'lar aynı sözleşmeyi sağlar | P0 |
| Budget guard test | Limit aşıldığında AI operasyonu bloklanır | P0 |
| AI tenant isolation test | AI çıktısı tenant boundary'yi aşamaz | P0 |
| Hermes boundary test | Hermes'te iş mantığı yok | P1 |
| Hermes event log tenant test | Event log tenant_id taşır | P1 |
| Idempotency test | Aynı event iki kez işlendiğinde aynı sonuç | P1 |

---

*Bu belge ARCHITECTURE_BACKBONE_AUDIT.md §8 (Karar #9) gereği üretilmiştir. Veri kaynakları: Constitution Madde 11-12, SAB.md Rule 17, REGISTRY.md §5, SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md, docs/known-debt.md.*
