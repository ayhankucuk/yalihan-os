---
document_id: GOV-DOC-001
document_owner: architecture
decision_owner: product-owner
status: active
canonical: true
evidence_level: REPO_VERIFIED
as_of_commit: 587e7020
last_reviewed: 2026-09-07
review_after: 2026-10-07
supersedes: null
---

# 📜 Yalıhan OS — Dokümantasyon Yaşam Döngüsü & Kanıt Sözleşmesi

> [!IMPORTANT]
> **Sınır ve Kapsam:** Bu sözleşme Yalıhan OS içindeki tüm dokümantasyon, mimari karar ve kanıt kayıtlarının yaşam döngüsünü belirler. Kod değişikliği veya deployment yetkisi vermez; yönetişim standartlarını bağlayıcı kılar.

---

## 🏛️ 1. Temel İlke: "One Concept, One Owner / Canonical Document"

1. Bir kavram veya mimari alan için **yalnızca tek bir kanonik kaynak** bulunabilir.
2. Yeni bilgi veya karar ortaya çıktığında yeni bir `.md` açmak yerine, ilgili kanonik belge **yerinde güncellenir (in-place update)**.
3. Dosya adlarında `final`, `v2`, `new-final`, `updated` gibi muğlak sürüm ekleri **KESİNLİKLE YASAKTIR**. Sürüm geçmişi Git commit'lerine bırakılır. Tarihli araştırma raporlarında ise açıkça `konu-YYYY-MM-DD.md` formatı kullanılır.

---

## 🏷️ 2. Zorunlu Belge Başlığı Metadatası (YAML Frontmatter)

Tüm kanonik belgeler, ADR'ler ve mimari araştırma raporları en başta şu metadata bloğunu taşımak zorundadır:

```yaml
---
document_id: ARCH-042                     # Değişmeyen tekil kimlik
document_owner: architecture              # Dokümanı güncelleyen sorumlu rol
decision_owner: product-owner             # Kararı onaylayan yetkili rol
status: active                            # active | proposed | stale_review_required | superseded
canonical: true                           # true | false
evidence_level: REPO_VERIFIED             # DOCUMENTED | REPO_VERIFIED | TEST_VERIFIED | PRODUCTION_VERIFIED
as_of_commit: 587e7020                    # Raporun üretildiği Git commit SHA
last_reviewed: 2026-09-07                 # Son inceleme tarihi (YYYY-MM-DD)
review_after: 2026-10-07                  # Bayatlık kontrol tarihi (Maks 30-90 gün)
supersedes: null                          # Varsa yerine geçtiği belge ID'si
---
```

---

## 📦 3. Evidence Packet (Kanıt Paketi) Standardı

Her mimari denetim, teknik araştırma ve agent handoff belgesinin sonunda şu kanıt paketi yer almalıdır:

```markdown
## 📦 Evidence Packet
- **Kaynak Dosyalar:** Değişen veya incelenen dosyaların tam göreceli yolları
- **İlgili Commit:** `git rev-parse HEAD`
- **Çalıştırılan Komutlar:** `php artisan test ...` vb.
- **Test Sonucu:** `PASS (X assertions)`
- **Production Durumu:** `NOT_APPLICABLE` veya `PRODUCTION_VERIFIED: URL/SSH kanıtı`
- **Açık Riskler:** Varsa teknik borç ve riskler
- **Sonraki Adım:** Devir teslim edilen sıradaki operasyon
```

---

## 🚫 4. "Bu Belge Ne Değildir?" Sınır Bloğu Standardı

Yanlışlıkla uygulama, yetki veya production onayı varsayımını engellemek için her analiz belgesinin başında şu uyarı yer alır:

```markdown
> [!IMPORTANT]
> **Sınır ve Kapsam:** Bu belge production doğrulaması değildir. Migration veya deploy yetkisi vermez. Öneriler `ACTION_PROPOSED` durumundadır.
```

---

## 🧭 5. Kanonik SSOT Dağılım Matrisi

| Konu / Alan | Kanonik Kaynak | İkincil / Destekleyici Rolü |
|---|---|---|
| **Authority & Governance** | `.sab/authority.json` & `.sab/*.md` | Değişmez anayasa referansı |
| **Proje Operasyonel Durumu** | `.project-brain/PROJECT_STATE.md` | Tek güncel durum kaynağı |
| **Kanıt ve Doğrulama İndeksi** | `.project-brain/EVIDENCE_INDEX.md` | Test ve VPS kanıt bağlantıları |
| **Mimari Kararlar (ADR)** | `docs/adr/` (ADR-xxxx-*.md) | `.project-brain/DECISION_LOG.md` yalnızca indeks/özet |
| **Aktif Yol Haritası (Roadmap)**| `docs/ERA_V/PHASE2-ROADMAP.md` | Kök `ROADMAP.md` yönlendirme dosyası |
| **Mimari Araştırma Raporları** | `docs/architecture/` (Tarihli .md) | Zaman damgalı analiz kayıtları |
| **Skill Talimatları** | `.agents/skills/*/SKILL.md` | Ajan operasyonel talimatları (taşınmaz) |
| **Tarihsel Kayıtlar** | `.project-brain/archive/` | Aktif SSOT değildir |

---

## 🛡️ 6. Non-Destructive Yönetişim (Tombstone Yönlendirmesi)

Bir belge geçerliliğini yitirdiğinde veya birleştirildiğinde **asla sessizce silinmez**. Yerine 2 satırlık bir yönlendirme bırakılır:

```markdown
# [ESKİ_BELGE_ADI] — ARŞİVLENMİŞTİR
> Bu belge geçerliliğini yitirmiş ve arşivlenmiştir.
> **Güncel Kanonik Kaynak:** [Yeni Belgeye Bağlantı](...)
> **Arşiv Nedeni & Tarih:** 2026-09-07 — SSOT Konsolidasyonu
```
