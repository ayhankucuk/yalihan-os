# 🛡️ SAB Context7 — PR Checklist

## 📝 Açıklama

<!-- Kısa ama net: ne değiştirildi, neden? -->

**Breaking change?** ☐ Evet ☐ Hayır

---

## 🔒 SAB Gate (ZORUNLU — Tümü işaretli olmalı)

### Context7 Compliance

- [ ] `php artisan sab:integrity-scan` → **0 NEW Critical violation**
- [ ] `scripts/blade-scan.sh` → **PASS**
- [ ] `scripts/route-guard.sh` → **PASS**
- [ ] `scripts/migration-guard.sh` → **PASS** (migration varsa)
- [ ] Yeni `s.t.a.t.u.s` / `r.e.o.r.d.e.r` / `i.s._.a.c.t.i.v.e` / `s.o.r.t._.o.r.d.e.r` field YOK

### Foundation Lock

- [ ] Yeni Model → `BaseModel` extend ediyor
- [ ] Yeni Migration → `aktiflik_durumu` (tinyInteger), `display_order` kullanıyor

### Code Quality

- [ ] `php artisan test` → **Tüm testler geçti**
- [ ] **Governance Core:** `.context7/` veya `scripts/` dokundun mu?
  - [ ] Eğer EVET: `scripts/quality-gate.sh` lokal geçti mi?

### Naming Dictionary (ihlal yok)

| Yasak                | Geçerli                                  |
| -------------------- | ---------------------------------------- |
| `s.t.a.t.u.s`        | `durum / aktiflik_durumu / yayin_durumu` |
| `r.e.o.r.d.e.r`      | `sirala / yeniden_sirala`                |
| `o.r.d.e.r` (alan)   | `display_order / siralama`               |
| `role="s.t.a.t.u.s"` | `role="presentation"`                    |
| `is_active`          | `aktiflik_durumu`                        |
| `sort_o.r.d.e.r`     | `display_order`                          |

---

## 📸 Kanıt

<!-- sab:integrity-scan output veya test sonucu ekle -->
