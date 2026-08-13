# DECISIONS — Mimari Kararlar

> Proje için alınan önemli mimari kararlar
> Her karar: Tarih | Karar | Gerekçe | Sonuç
> Format: Yıl-Ay-Gün

---

## 2026-08-13 | Oturum 116 | SAAB Program-Level Metrics Framework

### Karar: Üçlü Sağlık Çerçevesi — Canonical Reporting Model

**Karar:** Tek genel yüzde yerine üç ayrı program-level gösterge kullanılır.

**Gerekçe:** Tek genel yüzde farklı boyutları maskeliyor. Örneğin Booking.com kodu ~%100 tamam ama G35 onboarding bekliyor — capability completion yüksek, automation maturity daha düşük. Üçlü çerçeve bu ayrımı görünür kılıyor.

```
┌──────────────────────────────────────────────────────────────┐
│  YALIHAN PROGRAM-LEVEL METRICS — CANONICAL FRAMEWORK        │
├──────────────────────────────────────────────────────────────┤
│  Capability Completion:  ~72%                                │
│  Engineering Health:      ~62%                                │
│  Automation Maturity:     ~58%                                │
│  Current P0:              R002 — Test/CI Performance        │
│  Strategic Sequence:      R002 → M3 → M4                     │
│  M4 Operating Model:      Supervised Autonomy                 │
└──────────────────────────────────────────────────────────────┘
```

**Üç Gösterge:**

| Gösterge | Tahmin | Ne Anlatıyor |
|----------|--------|--------------|
| Capability Completion | ~72% | Planlanan sistemin ne kadarı inşa edildi |
| Engineering Health | ~62% | Test/CI/MCP/KB güvenilirliği |
| Automation Maturity | ~58% | Gerçek emlak işlerinin ne kadarı insan müdahalesi olmadan tamamlanıyor |

**P0 Zinciri:**
```
R002 (Test/CI Performance)
        ↓
Hızlı ve güvenilir certification altyapısı
        ↓
M3 — Enterprise Knowledge Runtime
        ↓
M4 — Autonomous Runtime
```

**M4 Supervised Autonomy Modeli:**

| Risk Sınıfı | Örnek | Davranış |
|-------------|-------|----------|
| LOW (otomatik) | Check-in hatırlatması, eksik fotoğraf tespiti, görev açma, kanal sync retry | AI karar verir → yapar |
| MEDIUM (insan onayı) | Fiyatı %15 değiştirme, rezervasyon iptal | AI önerir → insan onaylar |
| HIGH (SAAB/yetkili) | Para transferi, destructive migration, tenant güvenliği | AI algılar → SAAB karar verir |

**Sonuç:** Bu çerçeve resmen PROGRESS-TRACKER.md ve tüm agent memory dosyalarına canonical reporting modeli olarak kaydedildi.

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
