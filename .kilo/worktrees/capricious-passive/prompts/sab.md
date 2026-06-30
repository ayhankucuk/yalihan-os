# SAB — Production Seal

> Bu dosya Yalıhan AI OS'un teknik anayasasıdır.
> Uygulanmadan iş "Done" kabul edilmez.

## 1. Bağlayıcı Kurallar

1. Core (Ledger / CRM) **IMMUTABLE**
2. Core'a doğrudan write **YASAK**
3. Observer bypass **YASAK**
4. Silent catch **YASAK** (Fail-Fast zorunlu)
5. Raw DB write **YASAK** (migration hariç)
6. Projection tabloları **sadece Read Model**
7. Context7 ihlal toleransı = **0**
8. DLQ **zorunlu**
9. Event işleme **idempotent** olmalı

## 2. Naming Authority

Domain model alanları Türkçe:

| Yasak | Kanonik |
|-------|---------|
| status | yayin_durumu |
| active | aktiflik_durumu |
| type | tip |
| description | aciklama |

Bypass: `// context7-ignore`

## 3. Layer Isolation

```
Controller → Service → Repository → DB
```

Controller iş mantığı içermez.

## 4. Tenant Isolation

Cross-tenant veri erişimi **KESİNLİKLE yasak**.
Her query tenant scope içermeli.

## 5. AI Circuit Breaker

Her AI operasyonu `AiBudgetGuard::canExecute()` kontrolüne tabidir.

## 6. Financial Integrity

Bakiye mutasyonları sadece `recordDoubleEntry` ile yapılabilir.

## Kaynak

- Full SAB: `docs/SAB.md`
- Authority: `.sab/authority.json`
