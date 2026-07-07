# Sprint 4.2 Charter — Real CRUD Certification

**Sprint:** 4.2
**Start:** 2026-07-03
**Team:** Hermes
**Status:** 🔄 BAŞLANGIÇ

---

## Objective

> Tüm CRUD operasyonlarını (Create, Read, Update, Archive, Restore, Soft Delete) uçtan uca doğrulayarak YALIHAN OS'nin veri katmanını sertifikalandırmak.

---

## Success Criteria

| # | Kriter | Hedef |
|---|--------|-------|
| 1 | **Create** | Tüm domain'lerde veritabanı + audit + tenant + auth ile ✅ |
| 2 | **Read** | Yetkilendirme + tenant isolation ile ✅ |
| 3 | **Update** | Audit log + tenant scope ile ✅ |
| 4 | **Archive** | Soft delete + geri alma hazır ✅ |
| 5 | **Restore** | Arşivden geri alma çalışır ✅ |
| 6 | **Soft Delete** | Kalıcı silme yerine soft delete ✅ |
| 7 | **Playwright** | Görsel E2E testler yeşil ✅ |
| 8 | **Feature Tests** | Unit + Feature test suite ≥ %95 pass ✅ |

---

## Kapsam (In Scope)

### Domain'ler
- [ ] **İlan** — Create / Read / Update / Archive / Restore / Soft Delete
- [ ] **Kisi** — Create / Read / Update / Archive / Restore / Soft Delete
- [ ] **Talep** — Create / Read / Update / Archive / Restore / Soft Delete
- [ ] **Komisyon** — Create / Read / Update / Archive / Restore / Soft Delete

### Her Operasyon İçin Doğrulama Katmanları
- [ ] **Database** — Migration + seed + foreign key + index
- [ ] **Audit** — Activity log kaydı
- [ ] **Tenant** — tenant_id scope korunuyor
- [ ] **Authorization** — Rol bazlı erişim kontrolü
- [ ] **Playwright** — Browser tabanlı E2E test

---

## Kapsam Dışı (Out of Scope)

- ❌ Yeni özellik geliştirme
- ❌ UI redesign
- ❌ Mimari refactoring
- ❌ AI ajan zinciri (Sprint 4.3)
- ❌ Telegram entegrasyonu (Sprint 4.5)

---

## DoD (Definition of Done)

```
✅ Tüm CRUD operasyonları veritabanında çalışıyor
✅ Her operasyon için audit log kaydı var
✅ Tenant isolation tüm operasyonlarda korunuyor
✅ Authorization tüm endpoint'lerde aktif
✅ Playwright E2E testler yeşil
✅ Feature test suite ≥ %95 pass
```

---

## Retrospective Questions

1. Hangi domain'de en çok violation ile karşılaştık?
2. Tenant isolation açığı yakalandı mı?
3. Playwright hangi senaryoyu kaçırdı?

---

*Bu charter sprint süresince tek referans dokümanıdır. Diğer tüm doküman gereksiz.*
