---
id: memory-decisions
schema_version: 1.0
version: "1.0"
status: canonical
owner: agent
domain: governance
created_at: 2026-06-01
reviewed_at: 2026-07-25
review_due: 2027-07-25
supersedes: []
superseded_by: []
evidence: {}
tags: []
---

# DECISIONS — Mimari Kararlar

---

## 2026-07-26 | Sprint 12B Öncelik Sıralaması — Workspace Tenant Isolation

### Karar: Sprint 12B İçin Önceliklendirme

**Tarih:** 2026-07-26
**Gerekçe:** SAAB v8 kalite kapılarından biri Tenant Isolation. Sprint 12A (publish workflow) doğrulandıktan sonra veri izolasyonunu güçlendirmek uygun zemin oluşturuyor.

### Öncelik Sırası

| # | Adım | Açıklama |
|---|------|----------|
| 1 | **Workspace Tenant Isolation** | Publish/Unpublish'te workspace doğrulaması, cross-tenant erişim engeli, API tenant authorization testleri, event/audit kayıtlarında doğru workspace ilişkisi |
| 2 | **Cross-tenant Tests** | Tenant sınırlarının tüm katmanlarda (UI, API, Service) korunduğunu doğrulayan test suite |
| 3 | **State Machine Replay Tests** | Mevcut state transition kayıtlarından aynı geçişin güvenle yeniden oynatılabildiğinin doğrulanması |
| 4 | **Persistence Hardening Certification** | FK constraint, cascade delete, veri bütünlüğü sertifikasyonu |
| 5 | **Sprint 12C: Legacy Migration** | Güvenlik ve bütünlük tamamlandıktan sonra: IlanCrudService → ListingCrudService (feature flag + shadow) |

### Sprint 12B Definition of Done

Sprint tamamlanmış sayılmak için aşağıdaki kanıtlar üretilmelidir:

| # | Kanıt | Açıklama |
|---|-------|----------|
| 1 | Workspace isolation | Publish/Unpublish işlemleri yalnızca aynı workspace içinde çalışıyor |
| 2 | Cross-tenant blocked | Cross-tenant erişim girişimleri 403/404 ile engelleniyor |
| 3 | Replay deterministic | Replay testleri deterministik sonuç veriyor |
| 4 | FK integrity | FK ve veri bütünlüğü doğrulanıyor |
| 5 | CI green | Yeni eklenen testler CI'da yeşil |
| 6 | No new violations | bekci:health ve sab:integrity-scan sonuçlarında Sprint 12B ihlali yok |

### Paralel Sprint Hatları

| Track | Odak | Sprint'ler |
|-------|------|------------|
| A | Domain/Workflow Güvenilirliği | 12A→12B→12C |
| B | UI Modernizasyonu | 22-24→25→26 |

### Yol Haritası

```
Workspace Tenant Isolation
        ↓
Cross-tenant Tests
        ↓
State Machine Replay Tests
        ↓
Persistence Hardening Certification
        ↓
Sprint 12C Legacy Migration
```

### Neden Bu Sıralama?

1. **Tenant Isolation önce:** SAAB kalite kapısı, tüm veri erişiminin temeli. Önce bu sağlamlaştırılmalı.
2. **Cross-tenant Tests ikinci:** Isolation'ın tüm katmanlarda korunduğu bağımsız olarak doğrulanmalı.
3. **Replay Tests üçüncü:** State machine replay güvenilirliği, sonraki migration'ların güvenliğini garantiler.
4. **Hardening dördüncü:** Veritabanı constraint ve cascade kuralları, migration öncesi son kontroller.
5. **Legacy Migration en son:** Tüm güvenlik katmanları yerinde olduktan sonra refactoring yapılmalı.

### DLQ Pattern Hakkında

DLQ (Dead Letter Queue) pattern Sprint 12B kapsamına **alınmadı.** Mevcut `event dispatch` ve `audit log` zaten çalışıyor. DLQ bir sonraki sertleştirme adımı (Sprint 13+) olarak değerlendirilecek.

### Referans

- Sprint 12A: `BR-20260715-SAABv11` — Publish workflow certified
- Sprint 12B discovery: `.sab/sprint-12b-discovery/`
- Sprint 12C: Legacy IlanCrudService → ListingCrudService migration

---

## 2026-07-16 | Test İzolasyon + Lifecycle Yetkilendirme | Oturum 108

### Karar: Counter-based Lifecycle Authorization

**Problem:** `YalihanLifecycle::$isAuthorized` (bool) cross-test-class contamination yaratiyordu. Static boolean PHP OpCache ve process reuse nedeniyle testler arası sızıyordu.

**Çözüm:** Bool → Counter

```php
// Önce (problem)
public static bool $isAuthorized = false;

// Sonra (çözüm)
public static int $isTransitioningCounter = 0;
```

**Neden counter daha güvenli:**
- Nested `transition()` çağrılarında (auto-chain): her increment/decrement dengeli
- `finally` block her zaman counter'ı azaltır
- `TestCase::tearDown()` counter'ı sıfırlar

**İlişkili karar:** `parent::tearDown()` çağrısı eklendi — `RefreshDatabase` trait cleanup zinciri çalışsın diye.

**Kabul edilen risk:** Static state hâlâ DI yerine global. Sprint 13'te `ExecutionContext` injection planlandı.

---

> Proje için alınan önemli mimari kararlar
> Her karar: Tarih | Karar | Gerekçe | Sonuç
> Format: Yıl-Ay-Gün

---

## 2026-07-07 | AI GOVERNANCE v1.0 — Chief Engineer Direktifi

### Karar: Üç Ofis Yapısı — AI Governance v1.0

**Chief Engineer Kararı:** Üç AI birbirinin işini yapmaz. Her AI'ın yetki alanı net, çıktısı tanımlı.

```
╔═══════════════════════════════════════════════════════════════════╗
║                    YALIHAN OS — AI GOVERNANCE                   ║
╠═══════════════════════════════════════════════════════════════════╣
║                                                                   ║
║   🏛 SAAB (ChatGPT)          💻 VS CODE AI          🔬 ANTIGRAVITY ║
║   Architecture Office        Engineering Office      Research Office ║
║                                                                   ║
║   STRATEGY                   EXECUTION                 QUALITY       ║
║   ↓                          ↓                         ↓             ║
║   Ne yapılacağına            Nasıl yapılacağını        Doğru yapılıp  ║
║   karar verir                implement eder            yapılmadığını   ║
║                                                     araştırır       ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

### 🏛 SAAB — Architecture Office

| Özellik | Değer |
|---------|-------|
| Yetki | Stratejik Mimari Kararları |
| Kod yazar mı? | HAYIR |
| Model | ChatGPT (SAB, ADR, Sprint Charter) |
| Çıktıları | Sprint Charter, ADR, Architecture Review, Certification |

**Kuralları:**
```
1. SAAB kod YAZMAZ — sadece yön verir
2. SAAB implementasyon YAPMAZ — karar verir
3. SAAB test YAZMAZ — kalite kriteri belirler
4. SAAB research YAPMAZ — strateji üretir
```

---

### 💻 VS Code AI — Engineering Office

| Özellik | Değer |
|---------|-------|
| Yetki | Üretken Geliştirme |
| Kod yazar mı? | EVET |
| Model | Claude Code / Cursor / Cline |
| Çıktıları | Code, Pull Request, Tests, Migration, Implementation |

**Kuralları:**
```
1. VS Code mimari DEĞİŞTİREMEZ — SAAB kararlarını uygular
2. VS Code yetki alanı DIŞINA ÇIKAMAZ — sadece kod yazar
3. VS Code yeni domain EKLEYEMEZ — SAAB onayı gerekir
4. VS Code kural İHLAL EDEMEZ — SAB kurallarına uyar
```

---

### 🔬 Antigravity — Research Office

| Özellik | Değer |
|---------|-------|
| Yetki | Bağımsız Araştırma ve Doğrulama |
| Kod yazar mı? | HAYIR |
| Model | Kilo Agent |
| Çıktıları | Audit Report, Security Report, Performance Report |

**Görevleri:**
```
1. Repository Audit (dead code, cyclic dependency, anti-pattern)
2. Security Office (Webhook, OAuth, Queue, Tenant, JWT)
3. Performance Office (N+1, memory leak, cache, benchmark)
4. Technology Research (TKGM, Google Places, Vector DB)
5. Competitive Research (Airbnb, Booking, Hostaway)
6. Architecture Drift (SAAB ile kod uyumu kontrolü)
```

**OPERASYONEL KURAL:**
```
Antigravity hiçbir zaman:
  ✗ Mimariyi değiştirmez
  ✗ Uygulama kapsamını genişletmez
  ✗ Yeni domain eklemez
  ✗ ADR yazmaz
  ✗ Sprint Charter yazmaz

Antigravity sadece:
  ✓ Bağımsız doğrulama yapar
  ✓ Teknik araştırma yapar
  ✓ Güvenlik analizi yapar
  ✓ Performans analizi yapar
  ✓ Benchmark üretir
  ✓ SAAB'a öneri sunar
```

---

### Sprint Döngüsü

```
1. SAAB
   └── Sprint Charter yazar
   └── ADR onaylar (gerekirse)
   └── Quality Gate belirler
   └── Kapasite ataması yapar

2. Engineering Office (VS Code)
   └── Implementasyon yapar
   └── Test yazar
   └── PR açar

3. Antigravity (Kilo)
   └── Independent Audit
   └── Security Review
   └── Performance Analysis
   └── Architecture Drift Check

4. SAAB
   └── Sprint Review
   └── Certification
   └── Bir sonraki Sprint Charter
```

---

### Rol Atamaları

| Agent | Office | Rol |
|-------|--------|-----|
| ChatGPT | 🏛 SAAB | Architecture Office |
| Claude Desktop | 💻 Engineering | Code Implementation |
| Cursor | 💻 Engineering | Code Implementation |
| Cline | 💻 Engineering | CI/CD & Quality |
| Windsurf | 💻 Engineering | Framework Migration |
| Kilo | 🔬 Antigravity | Research Office |

---

### ERA III Uyumu

```
ERA I ────────────── ERA II ────────────── ERA III
Mimari Tasarım         Altyapı             Çalışan Capability
   ↑                      ↑                     ↑
SAAB oluştu          Bekçi oluştu       AI Governance v1.0
                    MCP aktif
                    Chief AI layer

ERA III Hedefi:
  ▸ Çalışan özellik üretmek
  ▸ Mimari değil, değer üretmek
  ▸ Net governance ile sürdürülebilir geliştirme
```

---

## 2026-06-27 | Oturum 48 | YALIHAN PLATFORM v2.0 — Dört Katmanlı Mimari

### Karar: Üç → Dört Katmanlı Mimari

**Karar:**
```
                    YALIHAN PLATFORM
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
   YALIHAN OS       AI Workforce        Integration Layer
 (Kullanıcı)      (Dijital Çalışanlar)   (Dış Sistemler)
        │
        ▼
Knowledge Layer
```

### Katman 1 — YALIHAN OS (Product)

**Kullanıcı Görür:**
- CRM
- Portföy
- İlan
- Airbnb
- Takvim
- Finans
- Dashboard
- Yetkilendirme
- UI/UX

**Sorumluluk:** İş kuralları, kullanıcı deneyimi

### Katman 2 — AI Workforce

**Kullanıcı Görmez:**
- Listing Agent
- Photo Agent
- Readiness Agent
- Recommendation Agent
- Description Agent
- CRM Agent
- Airbnb Agent
- Finance Agent
- Calendar Agent
- Drive Agent

**Event-Driven:** Her ajan Domain Event dinler:
```
PortfolioCreated
PhotoUploaded
ReadinessCalculated
DescriptionRequested
ReservationCreated
PaymentReceived
```

### Katman 3 — Integration Layer

**Dış sistemlerle konuşur:**
- OpenClaw (orkestrasyon altyapısı, ajan DEĞİL)
- n8n
- Telegram
- Google Drive
- Gmail
- Google Calendar
- WhatsApp (ileride)
- Airbnb API
- Sahibinden
- Hepsiemlak

### Katman 4 — Knowledge Layer

**Bilgi depoları:**
- Google Drive (doküman yönetimi)
- NotebookLM (AI bilgi çıkarımı)
- Dokümanlar

---

### Sprint 3.4.5 — Capability 1 v1

**Domain Event:**
```
ListingDescriptionGenerated
```

**Akış:**
```
Portfolio → Photo → Readiness → Recommendations
    → Context Builder → LLM → Draft
    → Owner Review → Accept → Persist
    → ListingDescriptionGenerated (Event)
```

**Event Consumer'lar:**
- Drive Agent (klasör açma)
- Telegram Agent (bildirim)
- CRM Agent (portföy güncelleme)
- NotebookLM Agent (bilgi çıkarımı)

---

### Karar: OpenClaw Rol Değişimi

**Önceki:** OpenClaw = Ajan
**Yeni:** OpenClaw = Orkestrasyon Altyapısı

OpenClaw ajan DEĞİL, ajanları koordine eden altyapı.

---

### v2.0 Mimari Özeti

```
YALIHAN PLATFORM
│
├── YALIHAN OS          (ürün, kullanıcı arayüzü)
├── AI Workforce         (iş yapan dijital ekip)
├── Integration Layer   (OpenClaw + n8n + dış servisler)
└── Knowledge Layer     (Drive + NotebookLM + dokümanlar)
```

**Gerekçe:** Büyüme ile mimari düzeni korumak için

---

## 2026-06-25 | Oturum 33 | AI Workspace Organizasyonu

### Karar: AI Workspace Yapısı

**Karar:**
```
yalihan2026/
├── agents/          → Agent instruction dosyaları
├── prompts/         → AI prompt & template dosyaları
├── knowledge/      → Konsolide bilgi tabanı
├── memory/          → Oturum hafızası
├── workflows/       → Automasyon workflow'ları
├── audits/          → Audit raporları
├── mcp/             → TypeScript MCP Bridge (KORUMA)
└── mcp-servers/     → JavaScript MCP Server (KORUMA)
```

**Gerekçe:**
- Agent instruction dosyaları merkezi yönetim
- Prompt ve knowledge ayrıştırma
- MCP dosyaları ayrı tutma (korunan alan)
- Kilo ve diğer agent'lar için tutarlı erişim noktası

**Sonuç:**
- Yeni agent hızlı onboarding (5 dosya ile başlar)
- Memory otomatik güncelleme mümkün
- Proje kendi kendini belgeliyor

---

## 2026-06-25 | Oturum 33 | MCP Server Kararı

### Karar: İki MCP Implementasyonu Korunacak

**Durum:**
- TypeScript Bridge (mcp/build/index.js) → Windsurf için
- JavaScript Server (mcp-servers/yalihan-bekci-mcp.js) → Cursor/Claude için

**Gerekçe:**
- Farklı IDE farklı transport tercih ediyor
- TS Bridge sadece 3 tool, JS Server 9 tool sunuyor
- JS Server daha olgun (knowledge base desteği)

**Karar:** Şimdilik tekleştirme YAPMA — her iki implementasyonu koru

---

## 2026-06-25 | Oturum 33 | Hafıza Stratejisi

### Karar: Oturum Bağımsızlığı İlkesi

**Karar:** Her konuşma oturumu bağımsızdır. Kalıcı hafıza için dosyalara yazılır.

**Hiyerarşi:**
```
1. memory/PROJECT_BRAIN.md    → Kalıcı, her oturum güncellenir
2. memory/CHANGELOG_AGENT.md  → Değişiklik kaydı
3. memory/SESSION_NOTES.md    → Oturum notları
4. memory/LEARNED_PATTERNS.md → Tekrarlanan hatalar
5. memory/DECISIONS.md        → Mimari kararlar
6. memory/WHERE_IS_WHAT.md   → Hızlı referans
7. memory/HOW_IT_WORKS.md     → Sistem nasıl çalışır
```

**Kullanım:**
- Oturum başı: `memory/PROJECT_BRAIN.md` oku
- Değişiklik sonrası: `memory/CHANGELOG_AGENT.md` güncelle
- Oturum sonu: `memory/SESSION_NOTES.md` güncelle
- Hata düzeltmesi: `memory/LEARNED_PATTERNS.md` güncelle

---

## 2026-06-27 | Oturum 44 | Faz Değerlendirmesi — Oturum Kapanışı

### YALIHAN OS — Faz Değerlendirmesi

---

#### Faz 1 — Engineering Foundation ✅ TAMAMLANDI

**Amaç:** Güvenilir bir geliştirme platformu oluşturmak

**Başarılar:**
- ✅ Repository kurtarıldı
- ✅ Git disiplini oturdu
- ✅ Recovery süreçleri tanımlandı
- ✅ SAB v5 LTS stabil hale geldi
- ✅ Runtime ve Memory katmanları oluşturuldu
- ✅ Test altyapısı güvenilir seviyeye geldi
- ✅ Tenant mimarisinin temel doğrulaması tamamlandı

**Bu faz tamamlandı.**

---

#### Faz 2 — Product Foundation 🚀 BAŞLIYOR

**Amaç:** İş değeri üretmek

**Odak:** "Sistemi geliştirmek" değil, "iş değerini geliştirmek"

**Sprint 3.4 İsmi:** YALIHAN OS v1.0 Product Foundation

---

### YALIHAN OS v1.0 — Kapsam

#### Modül 1 — AI İlan Asistanı
Her gün kullanılacak, tek ekranda:
- Fotoğraf analizi
- Eksik fotoğraf tespiti
- Airbnb açıklaması
- SEO başlığı
- Özellik önerileri
- Fiyat önerisi
- Eksik bilgi uyarıları
- Hazır ilan taslağı

#### Modül 2 — Portföy Merkezi
Her portföy için tek kartta:
- Teknik bilgiler
- Medya
- Rezervasyon durumu
- AI notları
- Bakım geçmişi
- Belge yönetimi

#### Modül 3 — Operasyon Merkezi
Airbnb tarafında tek panelden:
- Check-in
- Temizlik
- Bakım
- Misafir iletişimi
- Takvim

#### Modül 4 — AI Operasyon Merkezi
Altyapı (kullanıcı için görünmez):
- n8n, Telegram, NotebookLM, OpenClaw, AI Orchestrator
- Kullanıcı sadece sonucu görür

---

### Sprint Sonuç Tablosu (Yeni Metrik)

| Metrik | Hedef |
|--------|-------|
| Yeni kullanıcı özelliği | ✅ |
| Kullanıcıya kazandırılan süre | ✅ |
| Manuel işi otomatikleştirme | ✅ |
| Yeni AI yeteneği | ✅ |
| Engineering kalite | PASS |

---

### En Değerli Çıktı

> **Başarı metriği değişti:** "Repository temiz mi?" yerine "Kullanıcıya ne değer kattık?"

---

## 2026-06-27 | Oturum 44 | Proje Evreleri — Üç Dönem

### Dönem 1 — Kurtarma ✅ Tamamlandı
- Repository kurtarmak
- Git'i temizlemek
- Testleri ayağa kaldırmak
- SAB'ı oturtmak

### Dönem 2 — Platform ✅ Büyük Ölçüde Tamamlandı
- Runtime
- Memory
- Audit
- Engineering
- Tenant mimarisi

### Dönem 3 — Ürün 🚀 Başlıyor
Ürün geliştirme aşaması. Altyapı artık ürünü taşımaya hazır.

---

## 2026-06-27 | Oturum 44 | YALIHAN OS v1.0 Hedefi

### İlk AI Özelliği: AI İlan Asistanı

**Neden?** Her gün kullanılacak. Doğrudan kullanıcıya değer.

**Akış:**
```
Fotoğraf yükle
↓
AI analiz etsin
↓
Eksikleri bulsun
↓
Airbnb açıklaması yazsın
↓
SEO başlığı üretsin
↓
Fiyat önerisi versin
↓
Eksik fotoğrafları söylesin
↓
Checklist oluştursun
↓
NotebookLM'den villa bilgisini çeksin
↓
Hazır ilan oluşturulsun
```

### YALIHAN OS v1.0 Definition of Done

**Eski:** "40 dosya değişti"
**Yeni:** "Bir emlak danışmanı bunu bugün kullanabilir mi?"

Bu çok daha güçlü bir ölçüt.

### v1.0 İçeriği

- AI İlan Asistanı
- Portföy yönetimi
- Temel CRM
- Airbnb operasyon paneli
- Telegram bildirimleri

Bunlar çalışıyorsa, teknik olarak %100 tamamlanmasa bile gerçek dünyada kullanılabilir.

---

## 2026-06-27 | Oturum 44 | Sprint DoD Değişikliği

### Her Sprint Sonunda Sorulacak Soru

> **"Bu sprint sonunda YALIHAN Emlak ekibi yarın sabah hangi yeni özelliği gerçekten kullanabilecek?"**

Bu soru, projenin bundan sonraki yönünü belirlemek için en doğru pusula.

---

## 2026-06-27 | Oturum 44 | Session 45 Hedefi

### Oturum 45 İlk Hedef

Framework iyileştirmesi DEĞIL — **YALIHAN OS içinde son kullanıcının gerçekten kullanacağı ilk büyük AI özelliği**

Bu, stratejik pivotun ilk somut çıktısı olacak.

### SAB Kuralı (SAB v5 LTS için)

Her yeni değişiklik şu soruya cevap vermeli:

> **"Bu değişiklik YALIHAN OS kullanıcılarına doğrudan değer katıyor mu?"**

Cevap "hayır" ise → teknik borç listesine al, ertele.

### Sprint 3.4 Öncelik Sırası

| # | Özellik | Açıklama |
|---|---------|----------|
| 1 | AI İlan Asistanı | İlan oluşturma, iyileştirme, AI destekli açıklama üretimi |
| 2 | CRM Operasyonları | Portföy yönetimi, müşteri akışı, görev ve takip sistemi |
| 3 | Airbnb Operasyon Merkezi | Check-in süreçleri, temizlik/bakım takibi, takvim otomasyonu |
| 4 | AI Orchestrator | Gerçek iş akışları, n8n/Telegram/OpenClaw entegrasyonu |

---

## 2026-05-21 | Oturum 1-31 | Korunan Dosyalar

### Karar: Değiştirilemez Dosyalar

**Korunan:**
- `docs/SAB.md` — Teknik anayasa
- `.sab/authority.json` — Governance SSOT
- `app/Services/Ilan/IlanCrudService.php` — Tek yazma otoritesi
- `app/Services/AI/YalihanCortex.php` — AI orchestrator

**Gerekçe:**
- SAB değişikliği = checksum yenileme gerektirir
- Authority.json = tüm governance kurallarının kaynağı
- IlanCrudService = tek write authority, bypass = veri bozulması
- YalihanCortex = AI pipeline'ın merkezi, yanlış değişiklik = AI crash

---

## 2026-05-21 | Oturum 1-31 | Context7 Naming

### Karar: Türkçe Kanonik Alan Adları

**Karar:** Domain model alanları Türkçe, framework İngilizce

**Örnek:**
```php
// Domain model (Türkçe)
class Ilan {
    protected $fillable = ['yayin_durumu', 'aktiflik_durumu', 'tip'];
}

// Framework (İngilizce)
class Ilan extends Model {
    public $timestamps = true; // created_at, updated_at
    public function ilanlar(): HasMany {
        return $this->hasMany(Ilan::class);
    }
}
```

**Bypass:** `// context7-ignore` comment'i ile

---

## 2026-06-27 | Oturum 44 | Strategic Pivot — Product Development Phase

### Karar: Engineering Platform Tamamlandı, Ürün Geliştirme Başlıyor

**Önceki Faz:** Infrastructure Recovery
**Yeni Faz:** Product Development

**Mevcut Olgunluk:**
| Katman | Puan | Durum |
|--------|------|-------|
| Engineering Platform | 9.5/10 | ✅ Olgun |
| Product (YALIHAN OS) | — | 🟡 Geliştirilmeli |

**Neden Önemli:**
Oturum 44'te proje "Ayhan'ın projesi" olmaktan çıkıp "kendi kendini yönetebilen bir mühendislik sistemi" olma yoluna girdi.

### Karar: SAB v5 LTS Donduruldu

**Yapılmayacak:**
- Yeni constitution yazma
- Yeni runtime katmanı çıkarma
- Yeni governor ekleme
- Yeni engineering framework tasarlama

**Yapılacak:**
- SAB'ı sadece engineering standardı olarak kullan
- Mevcut kuralları uygula
- Gerekirse incremental fix

### Karar: Sprint Planı Güncelleme

| Sprint | Odak | İçerik |
|--------|------|--------|
| Sprint 3.3 | Feature Stabilization | AI Feature Tests, Auth, Tenant |
| Sprint 3.4 | Business Features | İlan AI, AI Search, AI Recommendations, AI Assistant |
| Sprint 3.5 | Observability | MCP, Monitoring, Metrics, Telemetry |
| Sprint 4 | Production | Hetzner, Deployment, Scaling |

### Karar: Başarı Metriği Değişti

**Eski Başarı:** Repository temiz mi? Testler yeşil mi? SAB uyumlu mu?
**Yeni Başarı:** Bu hafta YALIHAN OS kullanıcıya ne kazandırdı?

**Müşteri Soruları (Değer Burada):**
- AI ilan oluşturuyor mu?
- CRM hızlı mı?
- Airbnb operasyonunu kolaylaştırıyor mu?
- Telegram rapor gönderiyor mu?
- NotebookLM bilgi buluyor mu?
- OpenClaw ajanları iş yapıyor mu?

### Karar: Zaman Dağılımı

| Alan | Zaman | Durum |
|------|-------|-------|
| Engineering (Maintenance) | %20 | Artık maintenance modunda |
| Product Development | %80 | Ana iş artık bu |

### Karar: Sprint Roadmap

| Sprint | Odak | İçerik |
|--------|------|--------|
| Sprint 3.3 | Feature Stabilization | Auth, Tenant, AI Feature tests |
| Sprint 3.4 | Business Features | AI Listing Assistant, AI Property Analyzer, AI Search, CRM |
| Sprint 3.5 | Automation | n8n, Telegram, OpenClaw, MCP, Observability |
| Sprint 4 | Production | Hetzner, VPS, Monitoring, Deployment |

### Karar: Tek Hedef (1-2 Ay)

**Hedef:** YALIHAN OS'yi gerçek kullanıcıların kullanacağı bir ürüne dönüştürmek.

**Öncelik Alanları:**
- CRM
- İlan yönetimi
- AI Orchestrator
- Tenant sistemi
- Automation (n8n, OpenClaw)
- NotebookLM entegrasyonu
- Agent sistemi

**Gerekçe:**
Artık altyapı, bu hedefi destekleyecek kadar olgun. Bundan sonraki en büyük değer, altyapıyı yeniden inşa etmekten değil, onun üzerinde çalışan gerçek iş özelliklerini üretmekten gelecek.

### Karar: Product Readiness Değerlendirmesi

| Katman | Puan | Açıklama |
|--------|------|----------|
| Engineering Platform | 9.5/10 | ✅ Olgun, güvenilir |
| Product Readiness | 7.5/10 | 🟡 Üzerine inşa edilebilir |

**Sonuç:**
Bu aslında çok iyi bir denge. Altyapı artık ürünü taşımaya hazır. Şimdi sıra, YALIHAN OS'yi Bodrum'daki emlak operasyonlarını gerçekten hızlandıran, otomatikleştiren ve değer üreten bir platforma dönüştürmekte.

---

## 2026-05-21 | Oturum 1-31 | CQRS Mimarisi

### Karar: Write = Core DB, Read = Projection

**Write path:**
```
Controller → Service → IlanCrudService → Repository → DB
```

**Read path:**
```
Controller → Service → Projection Tables (listing_search_projection)
```

**Düzeltme:** Projection'a direkt yazma YASAK — sadece Event ile tetikle

---

## 2026-07-25 | EIOS Katmanları ve Evidence Model | Oturum 113

### Karar: Antigravity Rol Yeniden Tanımı

**Eski rol:** Kod doğrulama + mimari sertifikasyon
**Yeni rol:** `Documentation & Governance Auditor`
**Kapsam:** Markdown, ADR, Roadmap, Changelog, Canonical belgeler, dokümantasyon kalitesi

---

### Karar: Evidence Layer Model Oluşturuldu

Her sprint çıktısı dört kanıt katmanından geçer. Hiçbir katman başka bir katmanın çıktısını tek başına sertifikalandıramaz.

| Katman | Sahibi | Soru |
|--------|--------|------|
| Layer 1: Implementation | Claude Sonnet / Kilo | "Kod doğru mu?" |
| Layer 2: Execution Evidence | CI (PHPUnit) | "Kod gerçekten çalışıyor mu?" |
| Layer 3: Documentation | Antigravity | "Dokümantasyon doğru mu?" |
| Layer 4: Certification | SAAB | "Bu kanıtlarla sertifikasyon verilebilir mi?" |

Sertifikasyon durumları: `APPROVED` / `CERTIFIED` / `REJECTED`

---

### Karar: Sprint Packaging Standard v1.2

Sprint 21'den itibaren tüm sprint teslimatları dört artefakt içermelidir:

1. **Implementation Evidence** — commit hash, branch, dosyalar, kapsam
2. **Execution Evidence** — executed command, PHPUnit ham çıktısı, assertion/skip/fail
3. **Documentation Evidence** — CHANGELOG, Register, ADR, governance değişiklikleri
4. **Certification Package** — riskler, Decision Authority: SAAB, final decision

**v1.2'de kilitlendi.** Semantik versiyonlama: patch (v1.2.x), minor (v1.3), major (v2.0).

---

### Karar: EIOS Sprint Yaşam Döngüsü

```
Charter → Implementation → Evidence → Testing → Documentation → Certification → Handoff
```

SAAB v8 lifecycle ile uyumlu. Sprint 20'den itibaren tüm sprintler bu zinciri takip eder.

---

### Karar: Documentation Governance Modernization — Sprint 21 Odağı

Kod yazmak yerine EIOS altyapısı inşası:

1. Documentation Metadata Standard — her belge için zorunlu meta alanları
2. Documentation Lifecycle Pipeline — yaşam döngüsü kuralları
3. Canonical Inference Engine — otomatik kanoinik belirleme
4. Documentation Risk Engine — risk analizi
5. Semantic Duplicate Engine — anlamsal tekrar tespiti
6. Documentation Health Dashboard — tek sayı sağlık skoru

**Doküman artık sadece yazı değil.** Belgenin sahibi, durumu, yaşam döngüsü, kanıtı ve ilişkileri olan yönetilebilir varlık.

---

## 2026-07-26 | Wave 2 — IlanWizardController Remediation

### Karar: Sprint 12C Wave 2 Onayı

**Tarih:** 2026-07-26
**Board Question:** YALIHAN, IlanWizardController'ı SAB write-chain uyumlu hale getirebiliyor mu?
**Karar:** ✅ APPROVED FOR IMPLEMENTATION
**Charter:** BR-20260726-WAVE2
**Model:** Claude Sonnet 4.6
**Status:** 🚀 ACTIVE — IMPLEMENTATION AUTHORIZED
**SAAB Durumu:** 🟢 EXECUTION IN PROGRESS
**İlerleme:** ~50-60%
**Risk:** 🟡 Düşük-Orta

---

### SAAB Değerlendirmesi

| Alan | Durum |
|------|-------|
| Mimari problem tanımlandı | ✅ PASS |
| Hedef write-chain tanımlandı | ✅ PASS |
| Dokümantasyon güncellendi | ✅ PASS |
| Wave 2 kapsamı net | ✅ PASS |
| Implementasyona hazır | ✅ APPROVED |

---

### Wave 2 Başarı Ölçütleri

Wave 2 tamamlandığını söyleyebilmek için aşağıdaki mimari hedeflerin doğrulanması gerekir:

| # | Hedef | Açıklama |
|---|-------|----------|
| 1 | submitWizard() ORM write kaldırıldı | Doğrudan ORM yazma işlemleri kaldırılmış olmalı |
| 2 | Controller orchestration only | Controller yalnızca HTTP orchestration görevini üstlenmeli |
| 3 | Application Service entry point | Tüm yazma işlemleri IlanWizardApplicationService üzerinden başlamalı |
| 4 | Bridge as canonical entry | ListingCrudBridge tek canonical write entry point olarak kullanılmalı |
| 5 | Transaction boundary | İlan, fotoğraflar ve fiyatlandırma tek transaction içinde yönetilmeli |
| 6 | Feature flags preserved | OFF / ON / SHADOW modlarının davranışı korunmalı |
| 7 | Tests green | Mevcut testler yeşil kalmalı ve regresyon testleri eklenmeli |

### Wave 2 Done Kriterleri

| Kriter | Durum |
|--------|-------|
| Controller'da ORM write yok | ☐ |
| Controller'da doğrudan CrudService çağrısı yok | ☐ |
| IlanWizardApplicationService aktif | ☐ |
| ListingCrudBridge tek write entry point | ☐ |
| Tek transaction sınırı | ☐ |
| **Idempotency doğrulandı** | ☐ |
| **Shadow side-effect isolation doğrulandı** | ☐ |
| OFF / ON / SHADOW testleri geçti | ☐ |
| Tenant Isolation regresyonu geçti | ☐ |
| Authorization regresyonu geçti | ☐ |
| Rollback testleri geçti | ☐ |
| Wizard Feature Tests geçti | ☐ |
| Shadow parity raporu üretildi | ☐ |

### Ek Kalite Kapıları

**1. Idempotency**
submitWizard() aynı istek nedeniyle iki kez çalışırsa ikinci bir ilan oluşmamalı.

Test senaryoları:
- Aynı draft'ın tekrar submit edilmesi
- Ağ zaman aşımı sonrası retry
- Kullanıcının çift tıklaması

**2. Shadow Mode Side-Effect Isolation**
SHADOW modunda yalnızca karşılaştırma yapılmalı; ikinci bir gerçek iş etkisi oluşmamalı.

Yan etkiler tek kez gerçekleşmeli:
- Listing kaydı
- Fotoğraf kayıtları
- Fiyatlandırma kayıtları
- Event dispatch
- Queue job
- Audit log
- Timeline kayıtları

### Kalite Kapısı — Doğrulama Sırası

1. **Feature Tests** — Wizard submit akışı
2. **Shadow Parity Validation** — Legacy ↔ V2 sonuç karşılaştırması
3. **Transaction ve Rollback doğrulaması** — Rollback senaryoları
4. **Tenant Isolation ve Authorization regresyon testleri** — Authorization regresyonu
5. **Idempotency testleri** — Draft tekrar submit, retry, çift tıklama
6. **Side-effect isolation doğrulaması** — Shadow mode yan etki kontrolü
7. **Wave 2 Certification Review** — SAAB final kararı

---

### Bir Sonraki Değerlendirme Noktası

Analiz ve plan değil; **IlanWizardApplicationService implementasyonu** ile birlikte sunulacak test ve parity kanıtları. Bu kanıtlar başarıyla sunulduğunda Wave 2 için **CERTIFIED** değerlendirmesi yapılabilir.

---

### Mimari Hedef Yapı

```
HTTP
    ↓
Controller (Thin — orchestration only)
    ↓
Application Service (IlanWizardApplicationService)
    ↓
ListingCrudBridge (Feature Flag: OFF→IlanCrudService, ON→ListingCrudService, SHADOW→Both)
    ↓
Canonical Service
    ↓
Domain Writers
    ↓
Persistence (Transaction: Ilan + Fotoğraflar + Fiyatlandırma)
```

### Wave 2 Yönetim Kuralları

| # | Kural |
|---|-------|
| 1 | Her commit küçük ve geri alınabilir olsun |
| 2 | Her adım test edilebilir bir iş değeri üretsin |
| 3 | Bridge katmanı dışında yeni write path oluşturulmasın |
| 4 | Feature Flag davranışı hiçbir aşamada bozulmasın |
| 5 | Legacy davranışı parity doğrulanmadan kaldırılmasın |

### ⚠️ Scope Creep Uyarısı

> Şu anda en büyük risk mimari değil, **scope creep** olacaktır.

Wave 2 devam ederken:
- ❌ Yeni capability eklenmemeli
- ❌ Yeni mimari tartışmaları açılmamalı
- ❌ Yeni roadmap oluşturulmamalı
- ❌ Backlog büyütülmemeli

**Odak:** Yalnızca mevcut blueprint'in çalışan koda dönüşmesi.

### Phase 4 Geçiş Şartı

Phase 4 (Parity Validation) ancak şu soru "evet" cevabını alıyorsa başlamalıdır:

> **Legacy ve V2 aynı girdiler için aynı iş sonucunu üretiyor mu?**

Bu kanıt olmadan legacy temizliğine geçmek risk oluşturur.

### Wave 2 Exit Checklist

| # | Madde | Durum |
|---|-------|-------|
| 1 | Controller yalnızca HTTP orchestration yapıyor | ☐ |
| 2 | İş mantığı IlanWizardApplicationService içinde | ☐ |
| 3 | Tüm write işlemleri ListingCrudBridge üzerinden geçiyor | ☐ |
| 4 | OFF / ON / SHADOW modları aynı davranışı koruyor | ☐ |
| 5 | Transaction sınırı listing + photo + pricing işlemlerini kapsıyor | ☐ |
| 6 | Actor ve Workspace sunucu tarafında atanıyor | ☐ |
| 7 | Feature testleri ve regression testleri yeşil | ☐ |
| 8 | Rollback senaryosu doğrulanmış | ☐ |
| 9 | Shadow parity raporu üretilmiş | ☐ |

### Yönetim Seviyeleri

| Seviye | Hedef | Başarı Ölçütü |
|--------|-------|---------------|
| Engineering | IlanWizardController write-chain dönüşümü | Kod + Test |
| Architecture | Tek canonical write path | Shadow parity |
| Business | Manuel işin azalması | BAI artışı |

### Sprint 12C Çıkış Kriteri

| Kanıt Grubu | İçerik |
|-------------|--------|
| **1. Implementation Evidence** | Değiştirilen dosyalar, yeni write-chain, kaldırılan legacy write'lar |
| **2. Testing Evidence** | Feature testleri, regression testleri, transaction/rollback testleri |
| **3. Operational Evidence** | OFF/ON/SHADOW doğrulaması, rollback senaryosu, idempotency, side-effect isolation |
| **4. Certification Evidence** | 13 done kriteri, parity raporu, legacy cleanup onayı |

---

### Öncelik Sıralaması

| # | Adım | Öncelik |
|---|------|---------|
| 1 | submitWizard içindeki tüm ORM write'ları kaldır | P0 |
| 2 | IlanWizardApplicationService oluştur | P0 |
| 3 | Authenticated user_id ve workspace_id server-side ata | P0 |
| 4 | Typed DTO/Command kullanımı (SubmitWizardCommand) | P1 |
| 5 | Tek transaction sınırı | P1 |
| 6 | Idempotency + lockForUpdate | P1 |
| 7 | Shadow mode duplicate prevention | P2 |
| 8 | Event üretimi (ListingCreated, PhotosImported, SeasonalPricingConfigured) | P2 |
| 9 | Timeline entries (Wizard Submitted → Listing Created → Photos Added → Pricing Added) | P2 |

---

### DTO/Command Yapısı

```php
// SubmitWizardCommand — typed command object
final readonly class SubmitWizardCommand
{
    public function __construct(
        public int $draftId,
        public int $actorId,
        public int $workspaceId,
        public ListingData $listing,
        public array $photos,
        public ?SeasonalPricingData $pricing,
    ) {}
}

final readonly class ListingData
{
    public function __construct(
        public int $kategoriId,
        public int $yayinTipiId,
        public string $baslik,
        public string $aciklama,
        public float $fiyat,
        public array $location,
        // ...
    ) {}
}
```

---

### Transaction Sınırı

```php
// Application Service içinde
return DB::transaction(function () use ($command) {
    // 1. Draft ownership doğrulama (lockForUpdate)
    $draft = IlanDraft::whereKey($command->draftId)
        ->lockForUpdate()
        ->firstOrFail();

    $this->draftPolicy->assertCanSubmit($command->actorId, $draft);

    // 2. Idempotency kontrolü
    if ($draft->submitted_at !== null) {
        throw new DraftAlreadySubmittedException($draft->id);
    }

    // 3. Listing oluştur
    $listing = $this->bridge->store($command->listing);

    // 4. Fotoğraflar
    $this->photoWriter->replaceForListing($listing, $command->photos);

    // 5. Fiyatlandırma
    if ($command->pricing) {
        $this->pricingWriter->upsertForListing($listing, $command->pricing);
    }

    // 6. Events
    event(new ListingCreated($listing));
    event(new PhotosImported($listing, count($command->photos)));
    event(new SeasonalPricingConfigured($listing));

    // 7. Timeline
    $listing->timeline()->create([
        'event' => 'wizard_submitted',
        'actor_id' => $command->actorId,
    ]);

    return $listing;
});
```

---

### Nihai Hedef

Remediation tamamlandığında:
- **Owner Controller** ✅ (zaten SAB uyumlu)
- **Wizard Controller** → aynı write chain üzerinden çalışacak
- **API Actions** → aynı write chain üzerinden çalışacak

Bu da Sprint 12C'nin temel hedefi olan **tek, feature-flag kontrollü, geri alınabilir ve test edilebilir Listing yazma yolu** oluşturma amacını ilerletecektir.
