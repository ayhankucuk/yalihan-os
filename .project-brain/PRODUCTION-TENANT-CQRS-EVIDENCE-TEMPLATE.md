# Production Tenant/CQRS Kanıt Şablonu

**Tarih:** 2026-09-13  
**Durum:** ŞABLON — UYGULANMADI  
**Gerekli:** İnsan operatör + canlı iki tenant erişimi  
**Kapsam:** Tenant isolation + CQRS projection write→read döngüsü  

---

## AMAÇ

Tenant-A ve Tenant-B veritabanı kayıtlarını tamamen izole okuma-yazma-projection döngüsüyle doğrulamak. Bu, `BelongsToTenant` trait'inin ve CQRS projection'larının production'da gerçekten çalıştığının tek geçerli kanıtıdır.

---

## ÖN KOŞULLAR

- [ ] İki farklı canlı tenant (Tenant-A ve Tenant-B)
- [ ] Operatörün her iki tenant'a da yazma yetkisi olmalı
- [ ] Test verisi: birbirine yakın fiyatlı, aynı ilde, farklı tenant'larda en az 3'er ilan
- [ ] Sorgu için: `SELECT tenant_id, aktiflik_durumu FROM ilanlar WHERE ...`
- [ ] CQRS projection okuması: `SELECT * FROM listing_search_projections WHERE tenant_id = ?`

---

## OPERATÖR ADIMLARI

### AŞAMA 1 — Tenant-A: İlan Oluştur ve Yaz

```
Tenant-A ile giriş yap.
/owner/ilanlar/create
Step 1: Konut > Daire, Konut, İstanbul, Beşiktaş
Step 2: Boş form — hiçbir alan doldurma
Step 3: Fiyat: 5.000.000 TL
Step 4: GIS: İstanbul/Beşiktaş (haritaya tıkla)
Step 5: Submit

Beklenen: İlan kaydedildi, ID = Tenant-A-ILAN-01
```

**Kanıt:** Browser'dan `ilan_id`'yi not et.

---

### AŞAMA 2 — Tenant-B: Aynı Formu Tenant-B ile Dene

```
Tenant-B ile giriş yap.
Aynı formu aç — /owner/ilanlar/create
Step 1: Konut > Daire, Konut, İstanbul, Beşiktaş

Beklenen: 
- Tenant-A'nın ilanları görülmemeli (isolation)
- Tenant-B'nin ilanları görülmeli
- Form temiz başlamalı
```

**Kanıt:** Screenshot — Tenant-A verisi Tenant-B'de görünmüyor.

---

### AŞAMA 3 — Tenant-A: İlan Güncelle

```
Tenant-A ile giriş yap.
/owner/ilanlar/{Tenant-A-ILAN-01}/edit
Fiyat: 5.000.000 TL → 5.500.000 TL güncelle
Kaydet.

Beklenen: Güncelleme başarılı, fiyat 5.500.000 TL oldu.
```

**Kanıt:** Browser screenshot veya API yanıtı.

---

### AŞAMA 4 — Tenant-B: Tenant-A İlanına Erişim Denemesi

```
Tenant-B ile giriş yap.
Tarayıcıda şu URL'yi aç:
/owner/ilanlar/{Tenant-A-ILAN-01}/edit

Beklenen: HTTP 403 veya 404 (fail-closed / isolation)
Asla: Tenant-A'nın ilanı Tenant-B'ye görünmemeli
```

**Kanıt:** Screenshot — 403/404 yanıtı.

---

### AŞAMA 5 — Tenant-B: Kendi İlanını Oluştur ve CQRS Projection'ı Kontrol Et

```
Tenant-B ile giriş yap.
/owner/ilanlar/create
Step 1: Konut > Daire, Konut, İstanbul, Kadıköy
Step 3: Fiyat: 3.200.000 TL
Step 4: GIS: İstanbul/Kadıköy
Step 5: Submit

ID = Tenant-B-ILAN-01
```

**Kanıt:** Browser'dan ID not et.

---

### AŞAMA 6 — CQRS Projection Persistence Kontrolü

```
Database'e doğrudan bak (psql/mysql):
SELECT id, tenant_id, ilan_id, title, price, created_at, updated_at
FROM listing_search_projections
WHERE ilan_id IN (Tenant-A-ILAN-01, Tenant-B-ILAN-01)
ORDER BY tenant_id;

Beklenen:
| id | tenant_id | ilan_id      | price       | created_at | updated_at |
|----|-----------|--------------|-------------|------------|------------|
| .. | Tenant-A  | Tenant-A-01  | 5.500.000   | [zaman]    | [zaman]    |
| .. | Tenant-B  | Tenant-B-01  | 3.200.000   | [zaman]    | [zaman]    |

Tenant-A projection'ında Tenant-B verisi YOK.
Tenant-B projection'ında Tenant-A verisi YOK.
```

**Kanıt:** SQL sorgu çıktısı (screenshot veya raw output).

---

### AŞAMA 7 — Public API Tenant Isolation

```
Tenant-A API token'ı ile:
GET /api/v1/ilanlar/{Tenant-B-ILAN-01}

Beklenen: 404 veya 403
(Multi-tenant API'de tenant-scope zorunlu)

Tenant-A API token'ı ile:
GET /api/v1/ilanlar/{Tenant-A-ILAN-01}

Beklenen: 200, ilan detayları döner
```

**Kanıt:** HTTP yanıtları.

---

## KANIT ŞABLONU (Doldurulacak)

```json
{
  "test_id": "PROD-TENANT-CQRS-01",
  "date": "YYYY-MM-DD",
  "operator": "[OPERATOR_NAME]",
  "Tenant-A": {
    "id": "[TENANT_A_ID]",
    "ilan_id_created": "[ID]",
    "ilan_price_updated": "[NEW_PRICE]",
    "projection_found": true | false,
    "projection_price": "[PRICE]"
  },
  "Tenant-B": {
    "id": "[TENANT_B_ID]",
    "ilan_id_created": "[ID]",
    "isolation_enforced_on_A_ilan": true | false,
    "projection_found": true | false,
    "projection_price": "[PRICE]"
  },
  "cqrs_projection": {
    "Tenant-A_ilan_in_A_projection": true | false,
    "Tenant-A_ilan_in_B_projection": true | false,
    "Tenant-B_ilan_in_B_projection": true | false,
    "Tenant-B_ilan_in_A_projection": true | false,
    "all_isolated": true | false
  },
  "public_api": {
    "A_token_cannot_read_B_ilan": true | false,
    "A_token_can_read_own_ilan": true | false
  },
  "overall_result": "PASS | FAIL",
  "blocking_issues": []
}
```

---

## GEÇME KRİTERLERİ (HEPSİ DOĞRU OLMAZIL)

| # | Kriter | Beklenen |
|---|--------|----------|
| 1 | Tenant-A ilanı Tenant-B formunda görünmez | Form boş veya Tenant-B verisi |
| 2 | Tenant-A ilanına Tenant-B erişirse 403/404 | HTTP 403/404 |
| 3 | `listing_search_projections` Tenant-A kaydı Tenant-A'nın ilanından gelir | Projection tenant_id = Tenant-A |
| 4 | `listing_search_projections` Tenant-B kaydı Tenant-B'nin ilanından gelir | Projection tenant_id = Tenant-B |
| 5 | Cross-tenant projection sızdırması yok | Tenant-A projection'ında Tenant-B verisi YOK |
| 6 | Tenant-A API token Tenant-B ilanını okuyamaz | 403/404 |

**Tüm 6 kriter DOĞRU ise:** `TEST_VERIFIED` → Production Tenant Isolation PASS  
**Herhangi biri YANLIŞ ise:** P0 Incident açılır, deploy durdurulur.

---

## ROLLBACK

Test sırasında oluşturulan test ilanları:

```sql
DELETE FROM ilanlar WHERE id IN ('Tenant-A-ILAN-01', 'Tenant-B-ILAN-01');
DELETE FROM listing_search_projections WHERE ilan_id IN ('Tenant-A-ILAN-01', 'Tenant-B-ILAN-01');
```

> **Not:** Bu şablon sadece okuma amaçlıdır. İnsan operatör uygulamalıdır. Cline bu şablonu çalıştıramaz.
