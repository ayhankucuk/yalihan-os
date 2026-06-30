# 🧱 STANDART UYGULAMA BLOĞU (SAB — PRODUCTION SEAL)
Version: 24.2.0 (Phase 12: Monetization & Financial Seal)

SAB, projenin bağlayıcı teknik anayasasıdır.
Uygulanmadan hiçbir iş "Done" kabul edilmez.
SAB = Mimari Onay + Cognitive Guard + Drift Protection + Monetization Fortress.

---

## 1️⃣ BAĞLAYICI ANAYASA

1. Core (Ledger / CRM Write DB) IMMUTABLE'dir.
2. Core'a doğrudan write yasaktır. Mutation yalnızca Service katmanından yapılır.
3. Observer bypass yasaktır.
4. Silent catch yasaktır (Fail-Fast zorunlu). AST (Bekçi v2.1) ile denetlenir.
5. Raw DB write yasaktır (migration hariç).
6. Projection tabloları yalnızca Read Model içindir.
7. Integration katmanı Advisory Only'dir.
8. Context7 ihlal toleransı = 0.
9. DLQ zorunludur ve replay doğrulanmış olmalıdır.
10. Event işleme idempotent olmak zorundadır.
11. Core üzerinde ağır join/analytics çalıştırılamaz.
12. SAB yalnızca delta ile güncellenir.
13. Governance bypass eden değişiklik merge edilemez.
14. Bilişsel Muhafız (AST) bypass yasaktır.
15. Yaşayan Bellek (Learned Patterns) regresyonu bloklayıcıdır.
16. **[Phase 12] Multi-Tenant Financial Scoping:** Finansal query'lerde `tenant_id` zorunludur.
17. **[Phase 12] AI Circuit Breaker:** Her AI operasyonu `AiBudgetGuard` kontrolüne tabidir.
18. **[Phase 12] Financial Integrity:** Bakiye mutasyonları sadece `recordDoubleEntry` ile yapılabilir.

---

## 2️⃣ DOMAIN & SSOT KURALLARI

- Canonical alan adları authority.json tarafından belirlenir.
- ENUM yasaktır.
- Durum alanları tinyint olmalıdır.
- Migration ↔ Model $fillable %100 uyumlu olmalıdır.
- Ghost field üretilemez.

Dokümantasyonda literal yasaklı kelimeler alias ile anılır:
`[s-word]`, `[a-word]`, `[o-word]`.

---

## 3️⃣ KATMAN İZOLASYONU

Controller:

- İş mantığı içermez.

Service:

- Tüm iş kuralları burada.
- Shortcut yok.

Model:

- $fillable birebir eşleşir.
- Relationship eksiksiz.
- Lazy loading yok.

---

## 4️⃣ TEST SENKRONİZASYONU

Değişiklik varsa:

- Factory güncellendi
- Seeder güncellendi
- Assertion canonical alanla uyumlu
- Skip test yok
- Forbidden alan taraması yapıldı

---

## 5️⃣ OPERASYONEL SAĞLIK

Aşağıdakiler PASS olmalıdır:

```bash
php artisan projection:rebuild
php artisan projection:dlq:replay
php artisan projection:health
php artisan optimize:clear
php artisan sab:integrity-scan
php artisan bekci:run
php artisan test
```

---

## 6️⃣ GATE KRİTERLERİ

- Direct DB write: YOK
- Observer bypass: YOK
- Silent catch: YOK
- Context7: 0
- DLQ: AKTİF
- Worker restart: Idempotent

---

## 7️⃣ REGISTRY ZORUNLULUĞU (ENTERPRISE)

Her teknik değişiklikte:

- `docs/ai-logs` güncellendi
- `docs/registry/ai-decision-index.md` güncellendi
- `docs/registry/phase-history.md` güncellendi
- `docs/registry/architecture-timeline.md` güncellendi
- Governance verify PASS

**Registry güncellenmeden merge yapılamaz.**

---

## 8️⃣ SAB CHECKSUM & DRIFT PROTECTION (ZORUNLU)

SAB dosyası: `docs/SAB.md`
Checksum SSOT: `docs/SAB.sha256` (Regenerated)

Kurallar:

1. `SAB.md` değiştiyse `SAB.sha256` yeniden üretilmelidir.
2. Checksum eşleşmiyorsa commit FAIL.
3. CI pipeline drift varsa FAIL.
4. `SAB.sha256` tek başına değiştirilemez.
5. Drift = Merge Block.

Drift detection script'i CI ve pre-commit içinde zorunludur.

---

## 9️⃣ DEFINITION OF DONE

Bir iş Done sayılır ancak:

- Context7 PASS (0)
- Governance PASS
- Drift Detection PASS
- Test PASS
- Registry güncel
- Teknik borç oluşmadı

---

## 🔒 PRODUCTION SEAL

Aşağıdaki koşullar sağlanıyorsa:

- CQRS sağlıklı
- Projection HEALTHY
- DLQ doğrulandı
- Governance 0 ihlal
- SAB drift yok

**PRODUCTION SEAL: ACTIVE**

---

## SON İLKE

Yama yok.
Kural gevşemez.
SSOT dışına çıkılmaz.
Drift tolere edilmez.
Governance üstündür.

---

## 7️⃣ MALİ SUÇLAR VE FİNANSAL DENETİM (Phase 12)

Aşağıdaki eylemler "Mimari Suç" kabul edilir ve Bekçi tarafından bloklanır:

1. **Authority Leakage (Yetki Sızıntısı):** `tenant_id` filtresi olmayan her türlü finansal veri erişimi.
2. **Canonical Drift (Kanonik Sapma):** Finansal modellerde `amount`, `type`, `tur` gibi yasaklı (legacy) terimlerin kullanımı.
3. **Ghost Transaction (Hayalet İşlem):** Ledger dışında bakiye değiştiren her türlü otonom işlem.
4. **Budget Guard Bypass:** `AiBudgetGuard::canExecute()` kontrolü olmadan AI servisi çalıştırmak.

---

## 🔒 PHASE 15: CONTEXT ISOLATION (ADR-041)

17. **Context Isolation Standard:** Corporate memory conversation history değildir. Her Office P0 context budget'a tabidir. (ADR-041)

- Conversation history disposabledir — corporate memory approved artifact'lardır.
- Office session: 0–80K normal | 80–120K uyarı | 120–150K dondur | >150K yeni oturum.
- Office'ler sadece şunları kullanır: ADR, architecture decisions, office reports, ontology, capability specs.
- Tam conversation history yeniden yüklenemez.

---

*Bu anayasa Yalıhan AI OS'un teknik onurunu ve ticari geleceğini korur.*
