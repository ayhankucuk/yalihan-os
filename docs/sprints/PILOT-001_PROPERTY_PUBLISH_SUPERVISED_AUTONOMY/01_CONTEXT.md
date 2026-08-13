# PILOT-001 Discovery Evidence

**Date:** 2026-08-13
**Agent:** Kilo (PILOT-001 Session Start)
**Baseline:** `7981657`
**YDL Authority:** LIMITED_BY_BLOCKER — BLK-001 ⊄ Property Publish → **CONTINUE ✅**

---

## 1. Mevcut Publish Pipeline (Keşfedilen)

### State Machine

```
DRAFT
  ↓ (template hazır + AI completion)
PROMOTED
  ↓ (GovernanceTransitionGuard::canPublish = true)
PUBLISHED (yayinda)
```

**GovernanceTransitionGuard** (`app/Services/Governance/GovernanceTransitionGuard.php`):
```php
canPublish(GovernanceState $current): bool
// Sadece GovernanceState::PROMOTED geçişine izin verir
```

### Readiness Scoring Engine

**ReadinessEvaluatorService** (`app/Services/Workspace/ReadinessEvaluatorService.php`):

| Bileşen | Ağırlık | Eşik |
|----------|---------|------|
| Zorunlu alanlar | 70% | Her biri 100/n puan |
| Zorunlu belgeler | 20% | Her biri 100/n puan |
| AI hook completion | 10% | İlk 2 hook (title + description) |

**Eşik değerler:**

| Skor | Durum | Anlamı |
|------|--------|--------|
| 0–59 | `incomplete` | Yayınlanamaz |
| 60–89 | `warning` | Yayınlanabilir ama eksik var |
| 90–100 | `ready` | Yayınlanmaya hazır |

**API çıktısı:**
```php
[
    'score' => int,           // 0–100
    'status' => 'ready|warning|incomplete',
    'missing_fields' => [...],
    'missing_documents' => [...],
    'missing_ai_hooks' => [...],
    'field_score' => int,
    'document_score' => int,
    'ai_hook_score' => int,
    'summary' => string,
]
```

### Publish Blocking Rules

**CopilotRuleEngine** (`app/Services/AI/Copilot/CopilotRuleEngine.php`):

```php
publishReadinessCheck() // §9.2 Deterministic Backbone
```
Blokörler: Fiyat + Kategori + Fotoğraf + Başlık eksikse → `YAYIN_HAZIR_DEGIL` (priority 1, critical)

### Mevcut Human Approval Gate

**Blade** (`resources/views/owner/ilanlar/show.blade.php:167–183`):
```blade
@if($bc001AllReady)
    <button onclick="document.getElementById('bc001-approve-form').submit()">
        ✅ ONAYLA VE YAYINLA
    </button>
    <form action="{{ route('owner.ilanlar.yayinla', $ilan->id) }}">
@else
    {{ $bc001Total - $bc001ReadyCount }} bileşen henüz hazır değil
@endif
```

**PublishIlanAction** (`app/Actions/Api/V2/Ilan/PublishIlanAction.php`):
```php
$yayin_durumu = IlanDurumu::YAYINDA->value; // yazma otoritesi: IlanCrudService üzerinden
```

---

## 2. Supervised Autonomy — Mevcut vs. Pilot

### Mevcut (Bugünkü)
```
Danışman → blade görebiliyor (bc001AllReady) → buton tıklıyor → yayin_durumu = yayinda
```
- Agent kararı: yok (sadece danışman tıklıyor)
- YDL bağlantısı: yok
- Publish hazırlık otomasyonu: kısmen (readiness score hesaplanıyor)

### Pilot Hedefi
```
Agent → ydl:context okur (authority: FULL) → workspace/state okur
  → ReadinessEvaluatorService: score hesaplar
  → Missing fields/documents/hooks raporlar
  → "Yayına hazır mı?" → Skor + eksikler gösterir
  → İnsan onayı → Publish butonuna basar
  → ydl:session-summary --action CERTIFIED → ydl:apply --confirm
```
- Agent kararı: **deterministic** (YDL authority değil, ReadinessEvaluator)
- YDL bağlantısı: **tam** (session-start context + session-end event)
- Publish hazırlık otomasyonu: **tam** (eksik tespiti + görev önerisi)

---

## 3. Authority Intersection — BLK-001 vs. Property Publish

| Blocker | Scope | Property Publish ile kesişiyor mu? |
|----------|-------|-----------------------------------|
| BLK-001 | Booking.com production smoke test | **HAYIR** — paralel iş ✅ |

**Karar:** Property Publish pilotu BLK-001 tarafından ENGELLENMİYOR.

---

## 4. Supervised Autonomy Pipeline — PILOT-001 İçin

```
Agent Session Başlangıcı
    │
    ├── php artisan ydl:context
    │       → authority: LIMITED_BY_BLOCKER
    │       → BLK-001 ⊄ Property Publish scope
    │       → Decision: CONTINUE ✅
    │
    ├── Workspace / Property verisini oku
    │
    ├── ReadinessEvaluatorService::evaluate()
    │       → score (0–100)
    │       → status (incomplete/warning/ready)
    │       → missing_fields[], missing_documents[], missing_ai_hooks[]
    │
    ├── Skor < 90?
    │       → EKSİK: Agent görev önerisi üretir
    │       → "Fiyat ekle", "Fotoğraf yükle", "Başlık gir" vb.
    │
    ├── Skor ≥ 90 → "Yayına hazır"
    │       → Agent: "Yayınlanmaya hazır. İnsan onayı bekleniyor."
    │
    ├── İnsan onayladı → PublishIlanAction::handle()
    │       → yayin_durumu → YAYINDA (IlanCrudService üzerinden)
    │
    └── Oturum Sonu
            ├── ydl:session-summary \
            │   --action CERTIFIED \
            │   --target "PILOT-001: Property Publish" \
            │   --commit $(git rev-parse HEAD) \
            │   --dry-run
            ├── git add . && git commit
            └── php artisan ydl:apply --confirm
```

---

## 5. Boşluklar (Gap Analysis)

| # | Boşluk | Durum | Action |
|---|--------|-------|--------|
| G1 | Readiness score cockpit/API'de görünür | ✅ Mevcut | Değişiklik yok |
| G2 | Publish butonu blade'de insan onayı gerektiriyor | ✅ Mevcut | Supervised model = zaten mevcut |
| G3 | Agent, YDL context okumuyor | ❌ Eksik | `php artisan ydl:context` → agent prompt'a ekle |
| G4 | Agent eksik alanları otomatik tespit edemiyor | ⚠️ Kısmi | `ReadinessEvaluatorService` API olarak mevcut; agent prompt'a entegre edilmeli |
| G5 | YDL session summary üretilmiyor | ❌ Eksik | `ydl:session-summary` CLI mevcut; agent çalıştırmalı |
| G6 | Governance state → PROMOTED geçiş için otomatik mekanizma yok | ⚠️ Kısmi | `GovernanceTransitionGuard` + `GovernanceService` mevcut; agent bunu tetiklemeli |
| G7 | Pilot sonrası memory güncellenmiyor | ❌ Eksik | Agent `ydl:apply --confirm` çalıştırmalı |

---

## 6. Pilot Implementation Hedefleri

### Minimum Viable Pilot (MVP)

```
Agent, YDL context ile başlar:
    1. ydl:context okur → authority FULL
    2. Workspace readiness score hesaplar (ReadinessEvaluatorService kullanır)
    3. Eksik alanları raporlar (missing_fields, missing_documents, missing_ai_hooks)
    4. "Yayına hazır mı?" sorusuna deterministik cevap verir
    5. İnsan onayı → publish
    6. Oturum sonu → ydl:session-summary → ydl:apply --confirm
```

### Pilot-Only Değişiklikler (Mevcut kodu DEĞİŞTİRMEZ)

1. **Agent instruction'a YDL Phase 3 entegrasyonu ekle**
   - Oturum başı: `php artisan ydl:context`
   - Readiness evaluation agent prompt'a `ReadinessEvaluatorService` sonucu ekle
   - Oturum sonu: `ydl:session-summary --dry-run` → agent commit + `ydl:apply --confirm`

2. **Sabit proof-of-concept**
   - Mevcut `ReadinessEvaluatorService` zaten scoring yapıyor
   - Mevcut `GovernanceTransitionGuard` zaten state kontrolü yapıyor
   - Agent = YDL context + mevcut scoring service'leri okur, karar verir, insan onayı ister

---

## 7. KPI — Manual Time Reduction Baseline

**Bugünkü manuel süreç:**

| Adım | Bugün (dk) | Pilot sonrası (dk) |
|------|-------------|---------------------|
| Veri eksik tespiti (manuel) | 10 | 0 (AI okur) |
| Fotoğraf kontrolü (manuel) | 5 | 0 (AI okur) |
| Fiyat kontrolü (manuel) | 5 | 0 (AI okur) |
| Yayın onayı (insan) | 5 | 5 (korunur — supervised autonomy) |
| **Toplam** | **25 dk** | **5 dk** |

**Hedef: ≥80% reduction** — 25 dk → ≤5 dk

---

## 8. Sonraki Adım

**Pilot-001 Context/Discovery tamamlandı.**

Pilot Charter'daki 9 adımlı sekansın Step 1-2'si tamamlandı:
- ✅ Step 1: Charter + SAAB onayı
- ✅ Step 2: Discovery evidence (bu dosya)

**Step 3:** Pilot ortamı kurulumu (test workspace + sample data)

---
