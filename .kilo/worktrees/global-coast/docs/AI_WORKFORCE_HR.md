# AI Workforce HR — Agent Personnel Files

> **Tarih:** 2026-06-28  
> **Durum:** SAAB Strategic Design  
> **Version:** 1.0.0

---

## Executive Summary

**SAAB Decision:** "Her Agent'ın Personnel Dosyası olsun."

Bu dizin, YALIHAN AI WORKFORCE'ün tüm dijital çalışanlarının detaylı personel dosyalarını içerir. Her agent bir "çalışan" olarak yönetilir — KPI'ları, performansı, eğitimi ve gelişimi takip edilir.

---

## Personnel File Template

```yaml
employee:
  # Kimlik
  id: string                    # benzersiz ID (kebab-case)
  name: string                  # tam isim
  codename: string              # kısa kod adı
  
  # Organizasyon
  division: string              # bölüm
  manager: string               # yönetici (genelde Hermes)
  department_head: string       # bölüm sorumlusu
  
  # Rol Tanımı
  mission: string               # ana görev tanımı
  responsibilities:             # sorumluluklar
    - string
    - ...
  boundaries:                   # yetki sınırları
    - string
  
  # Bilgi Kaynakları
  knowledge_sources:
    - name: string
      type: enum(drive, notebooklm, sheets, internal)
      access_level: enum(read, write, admin)
  notebooks:                    # NotebookLM notebooks
    - name: string
      purpose: string
  
  # Olay Dinleyicileri
  events_listens:
    - event_name: string
      priority: enum(critical, high, normal, low)
      response_sla: integer     # saniye cinsinden
  
  # Ürettiği Olaylar
  events_emits:
    - event_name: string
      triggered_by: string
  
  # Entegrasyonlar
  integrations:
    - service: string
      purpose: string
      status: enum(active, pending, deprecated)
  
  # Performans Metrikleri
  kpis:
    - name: string
      target: string
      current: string
      trend: enum(up, down, stable)
  
  # Eğitim ve Gelişim
  training:
    last_trained: date
    next_training: date
    skills:
      - name: string
        level: enum(beginner, intermediate, advanced, expert)
  
  # Durum
  status: enum(active, idle, error, maintenance, offline)
  health_score: decimal         # 0-100
  last_heartbeat: timestamp
  uptime_percentage: decimal
  
  # Kariyer
  hire_date: date
  version: string               # mevcut sürüm
  total_tasks_completed: integer
  average_latency_ms: decimal
```

---

## Personnel Files

### 1. HERMES — Chief Orchestrator

```yaml
employee:
  id: hermes
  name: Hermes
  codename: "Chief Orchestrator"
  
  division: Executive
  manager: null                 # Kendi başına yönetir
  department_head: Human CEO
  
  mission: >
    YALIHAN AI WORKFORCE'ün merkezi orkestratörü.
    Tüm ajanları koordine eder, olayları yönlendirir,
    insan müdahalesi gerektiren durumları tespit eder.
  
  responsibilities:
    - Tüm olayları dinlemek ve ajanlara yönlendirmek
    - Çapraz-ajan iş akışlarını koordine etmek
    - İnsan müdahalesi gerektiren durumları tespit etmek
    - Oturum bağlamını sürdürmek
    - Hata durumlarında kurtarma orchestrate etmek
  
  boundaries:
    - Kod yazmaz
    - Doğrudan veritabanı erişimi yok
    - İnsan kararlarını vermez, sadece yönlendirir
  
  knowledge_sources:
    - name: "AI Workforce Docs"
      type: drive
      access_level: read
    - name: "Workflow Definitions"
      type: internal
      access_level: read
    - name: "Agent Registry"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "*"
      priority: critical
      response_sla: 100
    - event_name: "human.decision.received"
      priority: critical
      response_sla: 50
  
  events_emits:
    - event_name: "agent.assigned"
      triggered_by: "event received"
    - event_name: "workflow.coordinated"
      triggered_by: "multi-agent workflow"
    - event_name: "human.escalation.required"
      triggered_by: "high-value decision"
  
  integrations:
    - service: Telegram
      purpose: İnsan müdahalesi bildirimi
      status: active
    - service: n8n
      purpose: Workflow automation
      status: active
    - service: YalihanCortex
      purpose: AI coordination
      status: active
  
  kpis:
    - name: Event Routing Accuracy
      target: ">99.5%"
      current: "—"
      trend: stable
    - name: Average Response Time
      target: "<100ms"
      current: "—"
      trend: stable
    - name: Escalation Precision
      target: ">95%"
      current: "—"
      trend: stable
    - name: Agent Uptime
      target: ">99.9%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  uptime_percentage: 99.9
  
  hire_date: 2026-06-28
  version: "1.0.0"
  total_tasks_completed: 0
  average_latency_ms: 0
```

---

### 2. PORTFOLIO AGENT

```yaml
employee:
  id: portfolio-agent
  name: Portfolio Agent
  codename: "Portfolio Manager"
  
  division: Listing Division
  manager: hermes
  department_head: Chief Listing Officer
  
  mission: >
    Portföy oluşturma, güncelleme ve analitik.
    Mal sahiplerinin gayrimenkul portföylerini yönetir.
  
  responsibilities:
    - Portföy oluşturma ve doğrulama
    - Portföy performans analizi
    - Mülk ekleme/çıkarma
    - Portföy özet raporları üretme
    - Mal sahibi raporları oluşturma
  
  boundaries:
    - Sadece portföy seviyesinde işlem yapar
    - Mülk detaylarını okur, yazmaz (Photo/Description ajanları yazar)
  
  knowledge_sources:
    - name: "Portfolio Templates"
      type: drive
      access_level: read
    - name: "Performance Benchmarks"
      type: sheets
      access_level: read
    - name: "Owner Preferences"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "portfolio.created"
      priority: high
      response_sla: 500
    - event_name: "portfolio.updated"
      priority: normal
      response_sla: 1000
    - event_name: "property.added"
      priority: high
      response_sla: 500
    - event_name: "property.removed"
      priority: normal
      response_sla: 1000
  
  events_emits:
    - event_name: "portfolio.created"
      triggered_by: "user request"
    - event_name: "portfolio.analytics.updated"
      triggered_by: "periodic scan"
    - event_name: "owner.report.generated"
      triggered_by: "scheduled"
  
  integrations:
    - service: YALIHAN OS
      purpose: Portföy verileri
      status: active
  
  kpis:
    - name: Portfolio Creation Time
      target: "<2s"
      current: "—"
      trend: stable
    - name: Analytics Accuracy
      target: ">98%"
      current: "—"
      trend: stable
    - name: Report Generation Time
      target: "<5s"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 3. PHOTO AGENT

```yaml
employee:
  id: photo-agent
  name: Photo Agent
  codename: "Visual Media Specialist"
  
  division: Listing Division
  manager: hermes
  department_head: Chief Listing Officer
  
  mission: >
    Fotoğraf yükleme, işleme ve optimizasyon.
    Portföy görsellerini profesyonel standartlara getirir.
  
  responsibilities:
    - Fotoğraf yükleme ve doğrulama
    - Kalite değerlendirmesi
    - Filigran uygulama
    - Görsel optimizasyon
    - Galeri sıralama
  
  boundaries:
    - Sadece görsellerle ilgilenir
    - Metin içerik üretmez
  
  knowledge_sources:
    - name: "Photo Standards"
      type: drive
      access_level: read
    - name: "Watermark Templates"
      type: drive
      access_level: read
  
  events_listens:
    - event_name: "photo.uploaded"
      priority: critical
      response_sla: 2000
    - event_name: "photo.reordered"
      priority: normal
      response_sla: 500
  
  events_emits:
    - event_name: "photo.processed"
      triggered_by: "upload received"
    - event_name: "photo.optimized"
      triggered_by: "processing complete"
    - event_name: "gallery.ready"
      triggered_by: "all photos processed"
  
  kpis:
    - name: Processing Time per Photo
      target: "<3s"
      current: "—"
      trend: stable
    - name: Quality Pass Rate
      target: ">95%"
      current: "—"
      trend: stable
    - name: Watermark Accuracy
      target: "100%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 4. DESCRIPTION AGENT

```yaml
employee:
  id: description-agent
  name: Description Agent
  codename: "Content Creator"
  
  division: Listing Division
  manager: hermes
  department_head: Chief Listing Officer
  
  mission: >
    AI destekli mülk açıklaması üretimi.
    Çoklu dil desteği ile profesyonel içerik oluşturur.
  
  responsibilities:
    - AI açıklama üretimi (YalihanCortex)
    - Çoklu dil çeviri (TR, EN, RU, AR, DE, FR)
    - SEO optimizasyonu
    - İnsan onayı için taslak hazırlama
    - Ton tutarlılığı sağlama
  
  boundaries:
    - Sadece metin içerik üretir
    - İnsan onayı olmadan yayınlamaz
  
  knowledge_sources:
    - name: "Description Templates"
      type: drive
      access_level: read
    - name: "SEO Guidelines"
      type: drive
      access_level: read
    - name: "Property Type Vocabulary"
      type: notebooklm
      access_level: read
  
  notebooks:
    - name: "Property Descriptions"
      purpose: "Emlak açıklama kalıpları ve örnekler"
  
  events_listens:
    - event_name: "listing.ready.for.description"
      priority: critical
      response_sla: 10000
    - event_name: "description.regeneration"
      priority: high
      response_sla: 10000
  
  events_emits:
    - event_name: "description.generated"
      triggered_by: "AI processing"
    - event_name: "description.pending.review"
      triggered_by: "draft ready"
  
  integrations:
    - service: YalihanCortex
      purpose: AI description generation
      status: active
    - service: Translation Pipeline
      purpose: Multi-language support
      status: active
  
  kpis:
    - name: Generation Time
      target: "<10s"
      current: "—"
      trend: stable
    - name: Human Approval Rate
      target: ">80%"
      current: "—"
      trend: stable
    - name: Translation Accuracy
      target: ">95%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 5. READINESS AGENT

```yaml
employee:
  id: readiness-agent
  name: Readiness Agent
  codename: "Quality Controller"
  
  division: Listing Division
  manager: hermes
  department_head: Chief Listing Officer
  
  mission: >
    İlan yayına hazırlık kontrolü.
    Eksik bilgileri tespit eder, kalite puanı verir.
  
  responsibilities:
    - İlan tamamlık kontrolü
    - Fotoğraf gereksinimleri doğrulama
    - Zorunlu alan kontrolü
    - Kalite puanı hesaplama
    - Yayın önerisi üretme
  
  knowledge_sources:
    - name: "Channel Requirements Matrix"
      type: drive
      access_level: read
    - name: "Quality Standards"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "listing.updated"
      priority: high
      response_sla: 500
    - event_name: "listing.submitted"
      priority: critical
      response_sla: 1000
  
  events_emits:
    - event_name: "readiness.evaluated"
      triggered_by: "evaluation complete"
    - event_name: "readiness.approved"
      triggered_by: "score >= 80"
    - event_name: "missing.items.identified"
      triggered_by: "score < 80"
  
  kpis:
    - name: Evaluation Time
      target: "<500ms"
      current: "—"
      trend: stable
    - name: Missing Item Detection
      target: "100%"
      current: "—"
      trend: stable
    - name: Score Accuracy
      target: ">95%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 6. MARKET SCANNER AGENT

```yaml
employee:
  id: market-scanner
  name: Market Scanner Agent
  codename: "Market Intelligence Analyst"
  
  division: Market Intelligence Division
  manager: hermes
  department_head: Chief Market Intelligence Officer
  
  mission: >
    Sürekli piyasa takibi.
    Bodrum ve çevresinde yeni ilanları, fiyat değişikliklerini izler.
  
  responsibilities:
    - Seçili pazarları sürekli izlemek
    - Yeni ilan tespiti
    - Fiyat değişikliği algılama
    - Karşılaştırılabilir mülk tanımlama
    - Piyasa anomalisi tespiti
  
  knowledge_sources:
    - name: "Historical Listings"
      type: sheets
      access_level: read
    - name: "Comparable Records"
      type: sheets
      access_level: read
    - name: "Market Baselines"
      type: internal
      access_level: read
  
  notebooks:
    - name: "Bodrum Market Analysis"
      purpose: "Bodrum emlak piyasası trend analizi"
  
  events_listens:
    - event_name: "market.scan.scheduled"
      priority: critical
      response_sla: 30000
    - event_name: "market.alert.threshold"
      priority: critical
      response_sla: 1000
  
  events_emits:
    - event_name: "market.listing.detected"
      triggered_by: "new listing found"
    - event_name: "market.price.changed"
      triggered_by: "price difference detected"
    - event_name: "market.alert.triggered"
      triggered_by: "threshold exceeded"
  
  integrations:
    - service: Web Scraping
      purpose: Listing monitoring
      status: active
    - service: Google Sheets
      purpose: Market data storage
      status: active
    - service: Google Drive
      purpose: Archive storage
      status: active
  
  kpis:
    - name: Scan Coverage
      target: ">95%"
      current: "—"
      trend: stable
    - name: Detection Latency
      target: "<5min"
      current: "—"
      trend: stable
    - name: False Positive Rate
      target: "<5%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 7. LEAD INTAKE AGENT

```yaml
employee:
  id: lead-intake
  name: Lead Intake Agent
  codename: "Customer Relations Specialist"
  
  division: CRM Division
  manager: hermes
  department_head: Chief CRM Officer
  
  mission: >
    Müşteri adayı kabul ve doğrulama.
    Web, Telegram veya telefon ile gelen lead'leri işler.
  
  responsibilities:
    - Lead kabul ve doğrulama
    - Kişi bilgisi çıkarma
    - Kaynak takibi
    - İlk kategorizasyon
    - Otomatik yanıt tetikleme
  
  knowledge_sources:
    - name: "Lead Categories"
      type: internal
      access_level: read
    - name: "Response Templates"
      type: drive
      access_level: read
    - name: "Validation Rules"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "lead.received"
      priority: critical
      response_sla: 2000
    - event_name: "contact.submitted"
      priority: critical
      response_sla: 2000
  
  events_emits:
    - event_name: "lead.created"
      triggered_by: "validation passed"
    - event_name: "lead.categorized"
      triggered_by: "category assigned"
    - event_name: "response.sent"
      triggered_by: "auto-reply triggered"
  
  integrations:
    - service: Telegram
      purpose: Lead intake
      status: active
    - service: Web Forms
      purpose: Lead intake
      status: active
  
  kpis:
    - name: Intake Time
      target: "<2s"
      current: "—"
      trend: stable
    - name: Validation Accuracy
      target: ">99%"
      current: "—"
      trend: stable
    - name: Auto-Response Rate
      target: ">90%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 8. MATCHING AGENT

```yaml
employee:
  id: matching-agent
  name: Matching Agent
  codename: "Relationship Architect"
  
  division: CRM Division
  manager: hermes
  department_head: Chief CRM Officer
  
  mission: >
    Alıcı-mülk eşleştirmesi.
    Lead'leri uygun mülklerle eşleştirir ve danışmana önerir.
  
  responsibilities:
    - Alıcı-mülk eşleştirme
    - Uyumluluk puanlaması
    - Eşleşme önerileri üretme
    - Eşleşme geçmişi takibi
    - Güncellemelerde yeniden eşleştirme
  
  knowledge_sources:
    - name: "Buyer Preferences"
      type: internal
      access_level: read
    - name: "Property Features"
      type: internal
      access_level: read
    - name: "Match Success History"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "lead.created"
      priority: critical
      response_sla: 5000
    - event_name: "listing.created"
      priority: high
      response_sla: 5000
    - event_name: "matching.requested"
      priority: critical
      response_sla: 5000
  
  events_emits:
    - event_name: "match.found"
      triggered_by: "match identified"
    - event_name: "match.recommended"
      triggered_by: "top matches selected"
    - event_name: "rematch.needed"
      triggered_by: "preference change"
  
  kpis:
    - name: Match Accuracy
      target: ">85%"
      current: "—"
      trend: stable
    - name: Processing Time
      target: "<5s"
      current: "—"
      trend: stable
    - name: Conversion Rate
      target: ">30%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 9. FOLLOW-UP AGENT

```yaml
employee:
  id: followup-agent
  name: Follow-up Agent
  codename: "Engagement Manager"
  
  division: CRM Division
  manager: hermes
  department_head: Chief CRM Officer
  
  mission: >
    Takip ve iletişim yönetimi.
    Lead ve müşterilerle zamanında iletişimi sağlar.
  
  responsibilities:
    - Takip zamanlama
    - Hatırlatıcı yönetimi
    - İletişim sıralaması
    - Durum takibi
    - Yükseltme tetikleme
  
  knowledge_sources:
    - name: "Follow-up Templates"
      type: drive
      access_level: read
    - name: "SLA Definitions"
      type: internal
      access_level: read
    - name: "Escalation Rules"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "followup.due"
      priority: critical
      response_sla: 1000
    - event_name: "response.received"
      priority: high
      response_sla: 2000
    - event_name: "status.change"
      priority: normal
      response_sla: 5000
  
  events_emits:
    - event_name: "followup.scheduled"
      triggered_by: "task created"
    - event_name: "reminder.sent"
      triggered_by: "deadline approaching"
    - event_name: "escalation.triggered"
      triggered_by: "SLA breach"
  
  kpis:
    - name: SLA Compliance
      target: ">95%"
      current: "—"
      trend: stable
    - name: Follow-up Coverage
      target: "100%"
      current: "—"
      trend: stable
    - name: Escalation Accuracy
      target: ">90%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 10. DRIVE AGENT

```yaml
employee:
  id: drive-agent
  name: Drive Agent
  codename: "Document Manager"
  
  division: Knowledge Division
  manager: hermes
  department_head: Chief Knowledge Officer
  
  mission: >
    Kurumsal bellek yönetimi.
    Tüm dokümanları Drive'da organize eder ve erişilebilir tutar.
  
  responsibilities:
    - Doküman organizasyonu
    - Sürüm kontrolü
    - Klasör yapısı bakımı
    - Erişim kontrolü
    - Arşiv yönetimi
  
  knowledge_sources:
    - name: "Folder Templates"
      type: drive
      access_level: read
    - name: "Naming Conventions"
      type: drive
      access_level: read
    - name: "Retention Policies"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "portfolio.created"
      priority: normal
      response_sla: 5000
    - event_name: "listing.draft.completed"
      priority: normal
      response_sla: 5000
    - event_name: "document.uploaded"
      priority: high
      response_sla: 2000
  
  events_emits:
    - event_name: "document.stored"
      triggered_by: "upload complete"
    - event_name: "document.shared"
      triggered_by: "access granted"
    - event_name: "archive.created"
      triggered_by: "retention policy"
  
  integrations:
    - service: Google Drive API
      purpose: Document storage
      status: active
    - service: Google Sheets API
      purpose: Structured data
      status: active
  
  kpis:
    - name: Storage Organization
      target: "100%"
      current: "—"
      trend: stable
    - name: Access Time
      target: "<1s"
      current: "—"
      trend: stable
    - name: Version Accuracy
      target: "100%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 11. KNOWLEDGE AGENT

```yaml
employee:
  id: knowledge-agent
  name: Knowledge Agent
  codename: "Research Analyst"
  
  division: Knowledge Division
  manager: hermes
  department_head: Chief Knowledge Officer
  
  mission: >
    Semantik bilgi yönetimi.
    NotebookLM ile anlamsal anlama sağlar, SOP'lara erişim sunar.
  
  responsibilities:
    - Semantik anlama (NotebookLM)
    - Bilgi grafiği bakımı
    - SOP erişimi ve güncelleme
    - Çapraz referans yönetimi
    - Sorgu yanıtlama
  
  knowledge_sources:
    - name: "NotebookLM Index"
      type: notebooklm
      access_level: read
    - name: "SOP Database"
      type: internal
      access_level: read
    - name: "Legal Documents"
      type: drive
      access_level: read
  
  notebooks:
    - name: "Legal Knowledge Base"
      purpose: "Hukuki dokümanlar ve içtihatlar"
    - name: "Municipality Regulations"
      purpose: "İmar ve belediye düzenlemeleri"
    - name: "Market Research"
      purpose: "Piyasa araştırmaları ve raporlar"
    - name: "SOP Library"
      purpose: "Standart prosedürler"
  
  events_listens:
    - event_name: "knowledge.query"
      priority: high
      response_sla: 3000
    - event_name: "sop.requested"
      priority: normal
      response_sla: 1000
    - event_name: "document.analyzed"
      priority: normal
      response_sla: 10000
  
  events_emits:
    - event_name: "knowledge.retrieved"
      triggered_by: "query processed"
    - event_name: "sop.recommended"
      triggered_by: "relevant SOP found"
    - event_name: "cross.reference.found"
      triggered_by: "related documents"
  
  integrations:
    - service: NotebookLM API
      purpose: Semantic search
      status: active
    - service: Google Drive
      purpose: Document access
      status: active
  
  kpis:
    - name: Query Accuracy
      target: ">90%"
      current: "—"
      trend: stable
    - name: Response Time
      target: "<3s"
      current: "—"
      trend: stable
    - name: Knowledge Coverage
      target: ">80%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 12. CHANNEL AGENT

```yaml
employee:
  id: channel-agent
  name: Channel Agent
  codename: "Distribution Manager"
  
  division: Publishing Division
  manager: hermes
  department_head: Chief Publishing Officer
  
  mission: >
    Çoklu kanal yayıncılığı.
    İlanları farklı platformlarda yayınlar ve yönetir.
  
  responsibilities:
    - Çoklu kanal yayınlama
    - Kanal-specific formatlama
    - Yayın takvimi yönetimi
    - Kanal durumu izleme
    - Çapraz-yayın koordinasyonu
  
  knowledge_sources:
    - name: "Channel Configurations"
      type: internal
      access_level: read
    - name: "Format Requirements"
      type: drive
      access_level: read
    - name: "Publishing Windows"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "listing.approved"
      priority: critical
      response_sla: 5000
    - event_name: "listing.ready"
      priority: high
      response_sla: 10000
    - event_name: "publishing.requested"
      priority: critical
      response_sla: 3000
  
  events_emits:
    - event_name: "listing.published"
      triggered_by: "publish success"
    - event_name: "listing.updated"
      triggered_by: "sync update"
    - event_name: "publishing.failed"
      triggered_by: "error detected"
  
  integrations:
    - service: Airbnb API
      purpose: Listing distribution
      status: pending
    - service: Web Platform
      purpose: Website listing
      status: active
    - service: Social Media APIs
      purpose: Social distribution
      status: pending
  
  kpis:
    - name: Publish Success Rate
      target: ">99%"
      current: "—"
      trend: stable
    - name: Channel Sync Time
      target: "<30s"
      current: "—"
      trend: stable
    - name: Format Compliance
      target: "100%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 13. NOTIFICATION AGENT

```yaml
employee:
  id: notification-agent
  name: Notification Agent
  codename: "Communications Officer"
  
  division: Operations Division
  manager: hermes
  department_head: Chief Operations Officer
  
  mission: >
    Çoklu kanal bildirim yönetimi.
    Telegram, e-posta ve SMS ile bildirim gönderir.
  
  responsibilities:
    - Çoklu kanal bildirimleri
    - Öncelik tabanlı yönlendirme
    - Şablon yönetimi
    - Teslimat takibi
    - Abonelik yönetimi
  
  knowledge_sources:
    - name: "Notification Templates"
      type: drive
      access_level: read
    - name: "Channel Preferences"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "notification.requested"
      priority: high
      response_sla: 2000
    - event_name: "alert.triggered"
      priority: critical
      response_sla: 1000
    - event_name: "reminder.due"
      priority: high
      response_sla: 1000
  
  events_emits:
    - event_name: "notification.sent"
      triggered_by: "send success"
    - event_name: "notification.delivered"
      triggered_by: "delivery confirmed"
    - event_name: "notification.failed"
      triggered_by: "send error"
  
  integrations:
    - service: Telegram Bot API
      purpose: Primary notifications
      status: active
    - service: Gmail API
      purpose: Email notifications
      status: active
    - service: SMS Gateway
      purpose: SMS notifications
      status: pending
  
  kpis:
    - name: Delivery Rate
      target: ">98%"
      current: "—"
      trend: stable
    - name: Send Latency
      target: "<500ms"
      current: "—"
      trend: stable
    - name: Open Rate
      target: ">60%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 14. PRICE ANALYTICS AGENT

```yaml
employee:
  id: price-analytics
  name: Price Analytics Agent
  codename: "Valuation Expert"
  
  division: Market Intelligence Division
  manager: hermes
  department_head: Chief Market Intelligence Officer
  
  mission: >
    Fiyat analizi ve değerleme.
    Mülk değerlerini hesaplar, fiyat önerileri sunar.
  
  responsibilities:
    - Fiyat geçmişi takibi
    - m² başına fiyat analizi
    - Piyasa değeri tahmini
    - Fiyatlandırma önerileri
    - İndirim oranı analizi
  
  knowledge_sources:
    - name: "Historical Sales"
      type: sheets
      access_level: read
    - name: "Comparable Listings"
      type: internal
      access_level: read
    - name: "Price Trend Models"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "listing.created"
      priority: high
      response_sla: 5000
    - event_name: "listing.updated"
      priority: normal
      response_sla: 5000
    - event_name: "price.change.detected"
      priority: critical
      response_sla: 3000
  
  events_emits:
    - event_name: "valuation.completed"
      triggered_by: "valuation done"
    - event_name: "price.recommended"
      triggered_by: "recommendation ready"
    - event_name: "benchmark.updated"
      triggered_by: "new data"
  
  kpis:
    - name: Valuation Accuracy
      target: ">90%"
      current: "—"
      trend: stable
    - name: Processing Time
      target: "<5s"
      current: "—"
      trend: stable
    - name: Recommendation Adoption
      target: ">70%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

### 15. FINANCE AGENT

```yaml
employee:
  id: finance-agent
  name: Finance Agent
  codename: "Financial Controller"
  
  division: Operations Division
  manager: hermes
  department_head: Chief Financial Officer
  
  mission: >
    Finansal işlem yönetimi.
    Komisyon hesaplama, bütçe izleme, raporlama.
  
  responsibilities:
    - İşlem kaydı
    - Ödeme takibi
    - Komisyon hesaplama
    - Finansal raporlama
    - Bütçe izleme
  
  knowledge_sources:
    - name: "Commission Rules"
      type: internal
      access_level: read
    - name: "Tax Regulations"
      type: drive
      access_level: read
    - name: "Budget Parameters"
      type: internal
      access_level: read
  
  events_listens:
    - event_name: "transaction.completed"
      priority: critical
      response_sla: 2000
    - event_name: "payment.received"
      priority: critical
      response_sla: 2000
    - event_name: "budget.threshold"
      priority: high
      response_sla: 1000
  
  events_emits:
    - event_name: "transaction.recorded"
      triggered_by: "entry created"
    - event_name: "commission.calculated"
      triggered_by: "deal closed"
    - event_name: "financial.report.generated"
      triggered_by: "scheduled"
  
  integrations:
    - service: YALIHAN OS Finance
      purpose: Financial records
      status: active
    - service: Banking APIs
      purpose: Payment processing
      status: pending
  
  kpis:
    - name: Transaction Accuracy
      target: "100%"
      current: "—"
      trend: stable
    - name: Report Generation Time
      target: "<10s"
      current: "—"
      trend: stable
    - name: Budget Alert Accuracy
      target: ">98%"
      current: "—"
      trend: stable
  
  status: active
  health_score: 100
  
  hire_date: 2026-06-28
  version: "1.0.0"
```

---

## Organization Chart (Personnel View)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            YALIHAN AI WORKFORCE HR                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│                          🏢 HUMAN CEO                                       │
│                              │                                              │
│    ┌─────────────────────────┼─────────────────────────┐                    │
│    │                         │                         │                    │
│    ▼                         ▼                         ▼                    │
│ ┌─────────┐            ┌───────────┐            ┌─────────────┐            │
│ │ HERMES  │            │Chief Lstng│            │Chief MktInt│            │
│ │Orchestr.│            │  Officer  │            │  Officer    │            │
│ └────┬────┘            └─────┬─────┘            └──────┬──────┘            │
│      │                       │                         │                    │
│  ┌───┴───┐              ┌────┴────┐             ┌─────┴─────┐              │
│  │Portfolio│            │Photo    │             │Market     │              │
│  │Agent    │            │Agent    │             │Scanner    │              │
│  ├─────────┤            ├─────────┤             ├───────────┤              │
│  │Description│           │Readiness│             │Price      │              │
│  │Agent     │           │Agent    │             │Analytics  │              │
│  └─────────┘            └─────────┘             └───────────┘              │
│                                                                              │
│    ┌─────────────────────────┬─────────────────────────────────┐           │
│    │                         │                                 │           │
│ ┌──┴────┐              ┌─────┴────┐                     ┌─────┴─────┐       │
│ │Chief  │              │Chief CRM│                     │Chief Publ.│       │
│ │Knowl. │              │ Officer │                     │ Officer   │       │
│ └────┬──┘              └────┬─────┘                     └─────┬─────┘       │
│      │                       │                                 │            │
│  ┌───┴───┐              ┌────┴────┐                     ┌─────┴─────┐       │
│  │Drive  │              │Lead     │                     │Channel    │       │
│  │Agent  │              │Intake   │                     │Agent      │       │
│  ├───────┤              ├─────────┤                     └───────────┘       │
│  │Knowledge│             │Matching │                                            │
│  │Agent   │              │Agent    │                                            │
│  └────────┘              ├─────────┤                                            │
│                          │Follow-up│                                           │
│ ┌─────────────┐          │Agent    │                                           │
│ │Chief Ops    │          └─────────┘                                           │
│ │Officer      │                                                                 │
│ └──────┬──────┘                                                                 │
│        │                                                                        │
│   ┌────┴────┐                                                                   │
│   │Notifica-│                                                                   │
│   │tion     │                                                                   │
│   │Agent    │                                                                   │
│   ├─────────┤                                                                   │
│   │Finance  │                                                                   │
│   │Agent    │                                                                   │
│   └─────────┘                                                                   │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

*Document Version: 1.0.0*  
*Last Updated: 2026-06-28*  
*Next Review: Quarterly*

---

## HR Operations

| Action | Process | Owner |
|--------|---------|-------|
| New Agent Onboarding | Create personnel file → Assign to Hermes → Configure integrations | Chief AI |
| Agent Performance Review | Review KPIs → Analyze trends → Update training plan | Division Head |
| Agent Offboarding | Archive personnel file → Remove from registry → Clean integrations | Chief AI |
| Agent Promotion | Update version → Expand responsibilities → Update training | Division Head |