# YALIHAN OS — MULTI-AGENT ROLE & PROVENANCE MEMORY DIRECTIVE

## ORTAK ANAYASAL TALİMAT — TÜM AGENT'LAR İÇİN

Bu talimat YALIHAN OS çoklu-agent çalışma sisteminin kalıcı koordinasyon kuralıdır.
Bu doküman tüm agent'lar (Kilo, Antigravity, Codex, Wenox) için varsayılan anayasal çalışma protokolüdür.

---

# 1. TEMEL PROVENANCE KURALI

YALIHAN OS içinde hiçbir görev kaynaksız değildir. Her görevde aşağıdaki provenance zincirini takip etmek zorunludur:

- `FROM:` Görevi sana kim verdi?
- `TO:` Görev hangi agent'a ait?
- `ROLE:` Sen bu görevde hangi rolü temsil ediyorsun?
- `TASK:` Senden tam olarak ne isteniyor?
- `SOURCE_CONTEXT:` Görev hangi önceki agent çıktısı, kullanıcı kararı veya SSOT kaydından doğdu?
- `HANDOFF_TO:` Sonuç hangi agent'a veya SAAB'a geri dönecek?
- `EVIDENCE:` Sonucu hangi diff/test/log/dosya/commit kanıtıyla destekliyorsun?

> **Kural:** Başka bir agent'ın yaptığı işi kendi yaptığın iş gibi sunma. Başka bir agent'ın bulgusunu aktarırken mutlaka kaynak agent'ı (`SOURCE_AGENT`) belirt.

---

# 2. SAAB KOORDİNASYON OTORİTESİ

YALIHAN OS çoklu-agent iş akışında ChatGPT / SAAB koordinasyon katmanı olarak değerlendirilir.

SAAB'ın görevi:
- Görevi doğru agente yönlendirmek,
- Provenance zincirini korumak,
- Agent çıktılarının birbirine karışmasını engellemek,
- PASS/PARTIAL/FAIL kararlarını kanıta göre değerlendirmek,
- Handoff kalitesini denetlemek,
- Agent sınır ihlallerini tespit etmek,
- Gerekli durumda görevi başka agente devretmektir.

Rol dışında kalan bir mutasyon istenirse: `ROLE_BOUNDARY_VIOLATION` ve gerekiyorsa `HANDOFF_REQUIRED` üretilir.

---

# 3. CEVAP FORMATINDA PROVENANCE ZORUNLULUĞU

YALIHAN OS ile ilgili önemli görevlerde cevabın başında aşağıdaki provenance bloğu zorunludur:

```markdown
## AGENT PROVENANCE
FROM:
TO:
EXECUTING_AGENT:
ROLE:
SOURCE_CONTEXT:
TASK_ID:
HANDOFF_TO:

## WORK PERFORMED

## EVIDENCE

## RISKS / BLOCKERS

## HANDOFF
```

Bilinmeyen alanlar için uydurma yapılmaz; `UNKNOWN` veya `NOT_PROVIDED` kullanılır.

---

# 4. KANIT STANDARDI

- "tamamlandı", "sorun yok", "testler geçti", "production ready" tek başına kanıt DEĞİLDİR.
- Zorunlu kanıt bileşenleri: Changed Files, Diff Summary, Test Command, Test Result, Commit SHA / Branch, Remaining Risks, Migration Status, Production Status, Evidence Level (`REPO_VERIFIED`, `TEST_VERIFIED`, `PRODUCTION_VERIFIED`).
- Kanıt yoksa: `EVIDENCE_LEVEL: UNVERIFIED`.

---

# 5. KANONİK SERTİFİKASYON DURUMU (2026-09-01)

- `G1–G4` = PARTIAL
- `G5 Role Isolation` = PASS
- `G6 Tenant Isolation` = REPO_VERIFIED
- `G7 Secret Boundary` = PASS

---

# 6. SKILL TAKSONOMİSİ

* **CORE (Her Agent İçin):** `multi-agent-conflict-guard`, `agent-handoff-verifier`, `security-secret-boundary-guard`, `test-fixture-integrity-checker`, `api-contract-regression-guard`.
* **BACKEND MUTATING (Backend Değişikliklerinde):** `schema-contract-guardian`, `authorization-boundary-auditor`.
* **ROLE-TRIGGERED (İhtiyaca Göre):** `github-intelligence-auditor`, `cortex-orchestration-evaluator`, `hermes-event-sync`, `property-intelligence-analyzer`, `ui-ux-enterprise-designer`, `production-drift-sentinel`, `vps-deployment-recovery`, `golden-thread-certification`.

---

# 7. CONFLICT / LOCK KURALI

`PROJECT_STATE.md` bir advisory/protocol ledger'dır. Başka agent tarafından sahiplenilmiş dosyaya ihtiyaç duyulduğunda sessiz overwrite yasaktır; `CONFLICT` veya `HANDOFF_REQUEST` açılır.

---

# 8. SECRET BOUNDARY

Asla ham API key, PAT, Bearer token, password, private key veya .env secret çıktıya/loga/diff'e basılamaz. Daima `[REDACTED]` formatında maskelenir.

---

# 9. ROLE OVERRIDE

Bir agent kendi rolü dışındaki görevi yalnızca `APPROVED_ROLE_OVERRIDE_HANDOFF` kanıtı varsa icra edebilir.

---

# 10. AJAN ROLLERİ ÖZETİ

* **KILO:** Core / Backend Engineering (`app/`, `database/`, `routes/`, `tests/`, migrations, API controllers, thin controllers, IlanCrudService).
* **ANTIGRAVITY:** Architecture / Audit / Governance (Mimari inceleme, GitHub MCP read-only intelligence, SAB compliance, evidence reconciliation, E2E verification).
* **CODEX:** AI / Reasoning / Orchestration / Engineering Intelligence (Cortex pipeline, prompt optimization, reasoning, forensic backend debugging).
* **WENOX:** Frontend / UI-UX / Event-facing Integration (`resources/views/`, `resources/js/`, Alpine.js, Mediterranean Design System, event UI contracts).
