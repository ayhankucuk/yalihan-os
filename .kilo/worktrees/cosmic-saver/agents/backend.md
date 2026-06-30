# Backend Agent Instructions

> Yalıhan Emlak backend geliştirme kuralları.

## Proje Kimliği

- **Platform**: Yalıhan Emlak AI OS
- **Stack**: Laravel 10 / PHP 8.2+
- **Database**: MySQL (prod) + SQLite (test)
- **Cache**: Redis

## Yazma Zinciri

```
Controller → Service → IlanCrudService → Repository → DB
```

Controller'da **asla** `Eloquent::create/update/delete` YOK.

## Tenant Isolation

Cross-tenant veri erişimi KESİNLİKLE yasak. Her query tenant scope içermeli.

## Thin Controller Kuralı

```php
// ❌ YASAK
public function store(Request $request) {
    Ilan::create($request->all());
}

// ✅ ZORUNLU
public function store(StoreIlanRequest $request) {
    return $this->ilanCrudService->create($request->validated());
}
```

## KESİN YASAKLAR

| Yasak | Doğrusu |
|-------|---------|
| `env()` — app/ içinde | `config('key')` veya `app()->environment()` |
| Boş/sessiz catch bloğu | Log + rethrow veya `/** @sab-ignore-catch */` |
| `->first()` orderBy'sız | `->orderBy('id')->first()` |
| `\DB::` backslash | `use DB; ... DB::` |
| Raw DB write (migration hariç) | Service katmanı kullan |

## Context7 Naming

Domain model alanları Türkçe:

| ❌ Yasak | ✅ Kanonik |
|---------|-----------|
| `status` | `yayin_durumu` |
| `active` | `aktiflik_durumu` |
| `order` | `display_order` |
| `featured` | `one_cikan` |
| `type` | `tip` |
| `description` | `aciklama` |

## CQRS Kuralı

Write: Core DB
Read: Projection tabloları (`listing_search_projection`, vb.)
Projection'a direkt yazma — sadece Event ile tetikle.

## AI Servisleri

- AI servisleri `app/Services/AI/` altında
- YalihanCortex: `app/Services/AI/YalihanCortex.php`
- AI write operation: `AiBudgetGuard::canExecute()` kontrolü

## API Yapısı

```
api/v1/     → Public endpoints (auth yok)
api/advisor/ → Protected (Sanctum auth)
```

## Güvenlik

- Public endpoint'te owner_id, danisman_id, metadata DÖNDÜRME
- API response: her zaman JSON

## İlk Kontroller

```bash
php artisan sab:integrity-scan
php artisan bekci:health --detailed
./scripts/tools/antigravity-full-gate.sh --quick
```
