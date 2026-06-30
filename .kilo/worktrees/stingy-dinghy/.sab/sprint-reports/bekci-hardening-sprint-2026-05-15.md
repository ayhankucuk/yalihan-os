# Yalıhan Bekçi — Hardening Sprint Raporu
**Tarih:** 2026-05-15  
**Sprint:** Bekçi Güçlendirme + MCP Entegrasyonu  
**Süre:** Tek oturum  
**SAB Versiyon:** v6.1.2  

---

## Özet

Bu sprint'te Bekçi'nin 4 kritik kör noktası kapatıldı ve Model Context Protocol entegrasyonu tamamlandı. Bekçi artık CI/pre-commit'te değil, kod **yazılmadan önce** gerçek zamanlı devreye giriyor.

---

## Yapılan İşler

### 1. authority.json SSOT Tutarsızlığı Düzeltildi
**Problem:** `authority.json` governance SSOT içinde `active_workflow: "gold-line.yml"` yazıyordu. Gerçek CI dosyası `core-ci.yml`. Bekçi kendi kuralını çiğniyordu.

**Düzeltme:**
```json
// ÖNCE
"active_workflow": ".github/workflows/gold-line.yml",
"pipeline_name": "Gold Line CI"

// SONRA  
"active_workflow": ".github/workflows/core-ci.yml",
"pipeline_name": "Unified Core CI Pipeline"
```
`legacy_workflows_note` da güncellendi: `gold-line.yml` artık superseded listesinde.

---

### 2. Tenant Isolation Guard — RULE-T1
**Problem:** Finance domain'inde `tenant_id ?? 0` silent fallback pattern'i hiçbir guard tarafından yakalanmıyordu. Bu session'da 26 instance tespit edildi.

**Oluşturulan dosya:** `scripts/guards/ci-guard-tenant-isolation.sh`

4 kontrol katmanı:
- `RULE-T1-A`: `tenant_id ?? 0` — **BLOCKING**
- `RULE-T1-B`: `tenant_id ?: 0` — **BLOCKING**
- `RULE-T1-C`: Non-null-safe `auth()->user()->tenant_id` — warning
- `RULE-T1-D`: Finance dosyalarında TenantContextResolver eksikliği — warning

**Test sonucu:** 0 violation, 0 warning (Finance DI tamamlandıktan sonra)

---

### 3. Finance Domain — TenantContextResolver DI
**Problem:** BonusCalculator, CommissionCalculator, YalihanTreasury, TransactionService `auth()->user()?->tenant_id ?: throw new \RuntimeException()` pattern'ini inline kullanıyordu. `TenantContextResolver` inject edilmemişti.

**Düzeltilen dosyalar:**

| Dosya | Değişiklik | Instance |
|---|---|---|
| `BonusCalculator.php` | Constructor + TenantContextResolver DI | 6 |
| `CommissionCalculator.php` | Constructor + TenantContextResolver DI + getFinancialSettings fix | 6 |
| `YalihanTreasury.php` | TenantContextResolver constructor'a eklendi | 6 |
| `TransactionService.php` | Inline pattern değiştirildi | 8 |

**Toplam:** 26 inline `auth()` çağrısı → `$this->tenantResolver->resolve()->tenantId`

**Commit:** `fix(finance): inject TenantContextResolver into all Finance services`

---

### 4. Pre-Commit Hook — Shift-Left Enforcement
**Problem:** Tüm guard'lar CI'da (push sonrası) çalışıyordu. 638 dosyalık monolitik commit bu session başında neredeyse gerçekleşti.

**Oluşturulan:** `.git/hooks/pre-commit`

3 guard commit öncesi çalışıyor (sadece staged PHP dosyalarına karşı):
1. **Tenant Isolation** — BLOCKING
2. **Hardcoded Endpoint** — warning
3. **Naming Authority** (yeni dosyalar) — warning

Bypass: `git commit --no-verify` (audit trail gerektirir)

---

### 5. Bekçi MCP — herzaman uyanık
**Problem:** Bekçi sadece CI ve pre-commit'te devreye giriyordu. Kod yazılırken kör.

**Oluşturulan:** `mcp-servers/yalihan-bekci-mcp.js`

**Transport:** stdio (ESM, Node v22, `@modelcontextprotocol/sdk` v0.5.0 — zaten yüklüydü)

**6 araç:**

| Araç | Amaç | Tetikleme |
|---|---|---|
| `validate_file` | Dosyayı tüm guard'lardan geçir | Commit öncesi, code review |
| `get_canonical` | Context7 canonical isim sorgula | Yeni alan yazarken |
| `check_violation` | Kod snippet anlık kontrol | Yazarken, inline |
| `get_project_health` | Tenant/finance/naming/git durumu | Sabah, karar öncesi |
| `get_authority` | authority.json kural sorgula | Kural araştırma |
| `record_learning` | Knowledge base'e karar kaydet | Her önemli değişiklik |

**IDE entegrasyonu:**
- `claude.json` — Claude Desktop
- `.cursor/mcp.json` — Cursor

**Hot-reload:** `authority.json` saatte bir yenileniyor.

---

### 6. production-cleanup.sh Koruması
**Problem:** `scripts/ops/production-cleanup.sh` PHASE 4'te `rm -rf mcp-servers/` yapıyordu. Bekçi MCP production'da silinecekti.

**Düzeltme:** PHASE 4 artık `yalihan-bekci-mcp.js`'i whitelist'te tutuyor, sadece geliştirme MCP'lerini siliyor.

**Commit:** `fix(ops): production-cleanup.sh Bekci MCP'yi silmesin`

---

## Commit Geçmişi

```
fix(finance): inject TenantContextResolver into all Finance services
feat(bekci): tenant isolation guard + authority.json SSOT fix
feat(bekci): MCP server — herzaman uyanık
fix(ops): production-cleanup.sh Bekci MCP'yi silmesin
```

---

## Mevcut Durum — Guard Sonuçları

```
✅ ci-guard-tenant-isolation.sh   → 0 violation, 0 warning
✅ Pre-commit hook                 → aktif (staged dosyalara karşı)
✅ authority.json SSOT            → self-consistent
✅ Finance domain tenant isolation → tam
✅ Bekçi MCP                      → herzaman uyanık (stdio)
```

---

## Açık Kalan Görevler (Sonraki Sprint)

### Yüksek Öncelik

**AuditMcpServer ↔ Bekçi MCP Köprüsü**  
`app/Services/Bekci/AuditMcpServer.php` Telescope'u tarayıp `yalihan-bekci/knowledge/` dizinine PHP tarafından yazıyor. `yalihan-bekci-mcp.js` da aynı dizini okuyor. İkisi birbirinden habersiz çalışıyor. Node MCP'nin `scan_telescope` aracını expose etmesi veya PHP'nin event fırlatıp Node'un dinlemesi daha temiz bir mimari.

**`mcp:dev` script sorunu**  
`package.json`'da `"mcp:dev": "node --watch index.mjs"` var ama `index.mjs` mevcut değil. Dead script ya da Bekçi MCP'nin entry point'i olmalı. Düzeltme: `mcp-servers/yalihan-bekci-mcp.js`'e işaret etmeli.

### Orta Öncelik

**448 Exception Swallow Azaltma**  
`violation-baseline.json`'da `swallow_controller: 448` donmuş. Artmayı blokluyor ama azalmayı zorlamıyor. Sprint hedefi: her sprint 50 azalt, `catch {}` → proper exception handling.

**YalihanCortex 8 Kalan Heavy Method**  
3135 satırda hâlâ `getTopChurnRisks`, `analyzeMarketTrends`, `compareMarketPrices`, `suggestCategory` ve 4 daha var. Domain service'lere taşınmayı bekliyor.

### Düşük Öncelik

**`CONTEXT7_API_KEY` .env kontrolü**  
`mcp:context7` script'i bu key'e ihtiyaç duyuyor. `.env.example`'da tanımlı mı doğrulanmadı.

**Bekçi MCP `record_learning` otomasyonu**  
Şu an manuel çağrı gerektiriyor. Her commit'ten sonra otomatik çağrılabilir (post-commit hook).

---

## Mimari Notlar

**IDE'nin MCP tespiti hakkında:**  
IDE bu session'da 3 MCP tespit etti, yorumu yanlıştı:
- `FieldMcpController` → "MCP" = Measurement Control Point (saha cihazı), Model Context Protocol değil
- `AuditMcpServer` → PHP öğrenme motoru, gerçek MCP protokolü implement etmiyor
- Gerçek MCP: sadece `yalihan-bekci-mcp.js` ve `@upstash/context7-mcp`

**Bekçi'nin authority order'ı (sab-master-prompt.md v2.1.0):**
```
1. Human           → FINAL AUTHORITY
2. Real Code       → PRIMARY TRUTH  
3. authority.json  → GOVERNANCE SSOT
4. Runtime Truth   → RUNTIME EVIDENCE
5. Agent Suggestion → En düşük yetki
```

Bu sprint boyunca her IDE çıktısı (`wc -l`, `grep -c`, `git diff --stat`) ile doğrulandı. Theatrical output'a güvenilmedi.

---

*SAB v6.1.2 — Bekçi herzaman uyanık.*
