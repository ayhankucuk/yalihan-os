# 🏛️ YALIHAN OS — AGENT–SKILL ROUTER & ROUTING MATRIX SPECIFICATION
**Version:** 2.0.0 (ADR #007 Phase G2.3 Canonicalization)  
**Status:** CANONICAL OPERATIONAL ROUTING CONTRACT  
**Authority Source:** NO (`AGENTS.md` & `.sab/authority.json` remain Authority SSOT)  
**Human Decision Owner:** Ayhan  
**Session Owner:** Antigravity Parent Agent (task/runtime context)

> **CANONICAL CONTRACT NOTE:** This specification is accepted by Human Decision Owner Ayhan as a **Canonical Operational Routing Contract** (`TEST_VERIFIED / TOOL_RUNTIME` via G2.3 pilot). It is NOT an Authority Source. Router classifies, routes, constrains, and hands off tasks, but does not create domain authority or produce unverified architectural decisions.

---

## 📌 1. Amaç ve Mimari Vizyon

YALIHAN OS ekosisteminde Router bir **Otorite Kaynağı değildir**. Router'ın görevi mimari veya domain kararları üretmek değil; gelen görevi **Sınıflandırmak (`CLASSIFY`)**, **Yönlendirmek (`ROUTE`)**, **Kısıtlamak (`CONSTRAIN`)** ve **Devretmektir (`HANDOFF`)**.

### Hiyerarşi ve Aktör Yapısı:

```text
                        AYHAN
                Human Decision Owner
                         │
                         ▼
              ANTIGRAVITY PARENT AGENT
                   Session Owner
                         │
                         ▼
                AGENT–SKILL ROUTER
             (Classify / Route / Constrain)
                         │
                         ▼
              ROLE / EXECUTOR / SKILL
```

- **AYHAN:** Human Decision Owner (Nihai karar sahibi).
- **ANTIGRAVITY PARENT AGENT:** Session Owner (Oturum yöneticisi).
- **ROUTER:** Yönlendirme, kısıtlama ve yetki kontrol mekanizması (Authority değildir).

---

## 🚪 2. Default Explicit Governance Pointers & Task Contract

Bir görev subagent veya yürütücüye devredilirken, yönetişim kuralları prompt metnine kopyalanmaz. Görev kontratı içerisinde kanonik yol işaretçileri (**Canonical Path Pointers**) olarak taşınır:

### Varsayılan Görev İşaretçileri (Default Explicit Pointers):
1. **`AGENTS.md`** (Behavioral Constitution)
2. **`.sab/authority.json`** (Machine Authority)
3. **`.project-brain/PROJECT_STATE.md`** (Operational State & Evidence)

### Genişletilmiş Task Contract Şeması:

```json
{
  "task_id": "TASK-2026-09-17-001",
  "intent": "INSPECT_DIRTY_TREE_HYGIENE",
  "actor": "ANTIGRAVITY_PARENT",
  "executor": "CLINE",
  "domain_authority": "UNKNOWN",
  "governance_pointers": [
    "AGENTS.md",
    ".sab/authority.json",
    ".project-brain/PROJECT_STATE.md"
  ],
  "required_skill": "dirty-inventory-generator",
  "declared_write_scope": [],
  "allowed_actions": ["READ_FILE", "RUN_READONLY_COMMAND", "CLASSIFY"],
  "forbidden_actions": ["WRITE_FILE", "GIT_MUTATION", "PRODUCTION_MUTATION"],
  "stop_conditions": ["UNRESOLVED_AMBIGUITY", "PROPOSED_MUTATION_WITHOUT_SCOPE"],
  "required_evidence": "TEST_VERIFIED",
  "risk_level": "LOW"
}
```

> **Not:** `domain_authority` değeri belirsizse tahmin edilmez; `UNKNOWN` bırakılır. Çelişki durumunda görev durdurulur (`BLOCKED: ARCHITECTURAL_DECISION_REQUIRED`).

---

## 🎭 3. Agent Rolleri ve Yetki Sınırları (Model-Agnostik Katman)

Roller (`ROLE`) belirli model veya IDE isimlerine (Cline, Codex, Kilo vb.) bağlı değildir. Yürütücü (`EXECUTOR`), yalnızca ampirik runtime ve yetenek kanıtına (`capability / runtime evidence`) göre seçilir:

### Mevcut Runtime Kanıt Durumu (Evidence Status):
- **Antigravity Parent:** `TEST_VERIFIED` (Doğrulanmış test sınırları dahilinde)
- **Cline:** `TEST_VERIFIED` (G2.2B Session Owner Takeover sınırları dahilinde)
- **Codex:** `TOOL_RUNTIME UNKNOWN` (Kredi/runtime testi bekleniyor)

### Rol Yetki Detayları:

| Rol (`ROLE`) | Görevi | Varsayılan Kod Yazma Yetkisi (`declared_write_scope`) | Durdurma Kapısı (Stop Condition) |
|---|---|---|---|
| **Research** | Kod analizi, dirty tree sınıflandırma, bağımlılık haritalama. | ❌ YASAK (`NONE` / 0 dosya) | Belirsizlik veya bulgu raporu sunumu ardından stop. |
| **Forensic Research** | Hata/güvenlik kısıtı adli araştırması. | 📝 Sadece `.project-brain/KNOWN_ISSUES.md` (Çözüm `PROPOSAL ONLY`) | Bulguları `KNOWN_ISSUES.md`'ye yazdıktan sonra **STOP**. |
| **Architect** | Mimari karar önerisi (ADR), schema tasarımı, split-brain tespiti. | 📝 Sadece `docs/` & `.project-brain/` | Otorite belirsizliğinde: `BLOCKED_ARCHITECTURAL_DECISION` |
| **Implementer** | Kod yazımı, feature/bugfix geliştirme. | ⚙️ Sadece deklare edilen scope (`declared_write_scope`, Max 5-10 dosya) | Write zinciri veya scope aşımında stop. |
| **Verifier** | Bağımsız test, AST taraması, tenant izolasyon ve E2E doğrulaması. | 🧪 Varsayılan `READ-ONLY` (`tests/` için bile `declared_write_scope` şart) | Test başarısızsa kodu DÜZELTEMEZ; Implementer'a devreder. |
| **Integrator / Operations** | Git worktree birleştirme, release packaging, VPS deployment. | 🚀 Worktree & deploy scriptleri | Production mutation öncesi: **Human Gate** |

---

## 🛑 4. Aktör Duyarlı İnsan Kapıları (Actor-Aware Human Gates)

Normal insan (`HUMAN_USER`) tarafından yapılan manuel işlemler ile `AI / AUTOMATION` tarafından tetiklenen kritik yan etkiler (**side-effects**) teknik olarak farklı değerlendirilir.

### Kritik AI/Otomasyon İşlemleri (Açık Onay Gerektirenler):
1. **Price Mutation:** İlan/proje fiyatı (`fiyat`) güncelleme
2. **Reservation Cancellation:** Rezervasyon iptali
3. **Payment / Refund / Ledger Mutation:** Ödeme, iade, finansal defter kaydı
4. **Destructive / Core Deletion:** Kullanıcı, ilan veya ana CRM verisi silme (`forceDelete` / `TRUNCATE`)
5. **Legally Binding Outbound Message:** Müşteriye hukuki veya bağlayıcı dış mesaj gönderimi (WhatsApp/Mail)
6. **Production Mutation / Deployment:** Canlı veritabanı migration veya VPS deploy

> **Zorunluluk:** AI bu 6 işlemden birini tespit ettiğinde **Açık İnsan Onayı (`AYHAN AUTHORIZATION`)** almadan yürütülemez. Production mutation sonrasında ayrıca `PRODUCTION_VERIFIED` kanıtı aranır.

---

## 📊 5. Kanonik Kanıt Seviyeleri ve Tipleri (Evidence Model)

YALIHAN OS kanonik kanıt modelinde yalnız **5 Kanıt Seviyesi (Evidence Level)** mevcuttur:

```text
- REPO_VERIFIED
- TEST_VERIFIED
- PRODUCTION_VERIFIED
- INFERRED
- UNKNOWN
```

Kanıt Tipi (**Evidence Type**) ayrı bir alandır:
- `TOOL_RUNTIME` (Canlı tool icrası)
- `TOOL_CONFIGURATION` (Konfigürasyon varlığı)
- `DOCUMENTED` (Dokümante edilmiş iddia)
- `AST_INVARIANT` (Statik AST analizi)
- `DATABASE_SCHEMA` (Veritabanı şeması)

---

## 🗺️ 6. Kanıta Dayalı Agent–Skill Routing Matrisi

| Görev Türü | Target Scope | Rol | Önerilen Skill | Yazma Sınırı | Sonraki Kapı |
|---|---|---|---|---|---|
| **Dirty Tree Analizi** | Working tree | Research | `dirty-inventory-generator`, `git-worktree-hygiene` | ❌ Read-Only | Human / Integrator |
| **Adli Araştırma** | Known debt / bugs | Forensic Research | `knowledge-curator` | 📝 `.project-brain/KNOWN_ISSUES.md` | STOP / Proposal |
| **Domain Authority** | Models, services | Architect | `saab`, `yalihan-os-architect` | 📝 Sadece ADR | Human Decision Owner |
| **Laravel Backend** | `app/Controllers/*`, `app/Services/*` | Implementer | `core-engineering-guard` | ⚙️ Declared Scope | Verifier |
| **Blade / Alpine UI** | `resources/views/**/*.blade.php` | Implementer | `blade-alpine-runtime-guardian` | ⚙️ Declared Scope | Verifier |
| **API Contract** | `app/Http/Controllers/Api/*` | Implementer / Verifier | `api-contract-envelope-guardian` | ⚙️ Declared Scope | Verifier |
| **View Audit** | `resources/views/*` | Verifier | `page-design-architecture-auditor` | ❌ Read-Only | Implementer |
| **Tenant İzolasyonu** | Policy, TenantScope | Verifier | `core-engineering-guard` | 🧪 Scope verilmişse `tests/` | Release Gate |
| **Deploy / Operations** | VPS Docker, migrations | Operations | `vps-deployment-recovery` | 🚀 Operational | **Human Gate (Ayhan)** |
