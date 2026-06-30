# Yalıhan 2026 — Mimari Derin Dalış Dokümanı

**Tarih:** 2026-05-23
**Oturum:** 25+
**Kapsam:** 5 Kritik Mimari Soruya Kapsamlı Yanıtlar

---

## 📋 İçindekiler

1. [Neden BaseModel Tüm Modellerde Zorunlu?](#1-neden-basemodel-tüm-modellerde-zorunlu)
2. [IlanCrudService Write Authority Neden Mutlak?](#2-ilancrudservice-write-authority-neden-mutlak)
3. [Context7 Kanonik İsimlendirme Mantığı](#3-context7-kanonik-isimlendirme-mantığı)
4. [Antigravity Araçları Neden Git-Diff Tabanlı?](#4-antigravity-araçları-neden-git-diff-tabanlı)
5. [SAB Yönetişim Modeli Nasıl Çalışır?](#5-sab-yönetişim-modeli-nasıl-çalışır)

---

## 1. Neden BaseModel Tüm Modellerde Zorunlu?

### Sorun: Eloquent Model Davranış Tutarsızlığı

Laravel projelerinde her model kendi başına `Illuminate\Database\Eloquent\Model` extend ettiğinde:

- **Scope tutarsızlığı**: Bazı modellerde `aktiflik_durumu` scope'u var, bazılarında yok
- **Audit trail eksikliği**: Hangi modellerde `created_by`, `updated_by` tracking var, hangilerinde yok?
- **Soft delete karmaşası**: Bazı modeller soft delete kullanıyor, bazıları hard delete
- **Timestamp standardı yok**: `created_at`, `updated_at` bazı tablolarda var, bazılarında yok

### Çözüm: BaseModel Soyutlaması

```php
// app/Models/BaseModel.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasActiveScope;
use App\Traits\HasAuditTrail;

abstract class BaseModel extends Model
{
    use SoftDeletes, HasActiveScope, HasAuditTrail;

    // Tüm modellerde ortak davranışlar
    protected $guarded = ['id'];

    public $timestamps = true;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
```

### Kazanımlar

1. **Tek Noktadan Kontrol**: Tüm modellerde ortak davranış değişikliği tek yerden yapılır
2. **Scope Garantisi**: Her model otomatik olarak `->active()` scope'una sahip
3. **Audit Trail**: `created_by`, `updated_by` tracking tüm modellerde standart
4. **Soft Delete Standardı**: Tüm modeller soft delete kullanır (hard delete istisnai)
5. **Governance Uyumu**: SAB kuralları BaseModel üzerinden enforce edilir

### Gerçek Dünya Örneği

```php
// ❌ ÖNCE (Tutarsız)
class Ilan extends Model { /* scope yok */ }
class Kisi extends Model {
    public function scopeActive($q) { return $q->where('aktiflik_durumu', 1); }
}

// ✅ SONRA (Tutarlı)
class Ilan extends BaseModel { /* otomatik scope */ }
class Kisi extends BaseModel { /* otomatik scope */ }

// Her ikisi de aynı şekilde çalışır:
Ilan::active()->get();
Kisi::active()->get();
```

---

## 2. IlanCrudService Write Authority Neden Mutlak?

### Sorun: Dağınık Yazma Mantığı

Yalıhan 2026 öncesi durum:

```php
// Controller'da
$ilan = Ilan::create($request->all()); // ❌ Mass assignment riski

// Başka bir controller'da
$ilan = new Ilan();
$ilan->baslik = $request->baslik;
$ilan->save(); // ❌ Validation yok

// API endpoint'te
Ilan::where('id', $id)->update(['yayin_durumu' => 'yayinda']); // ❌ Business logic bypass
```

**Sorunlar:**
- Validation tutarsız
- Business rule'lar bazı yerlerde uygulanıyor, bazılarında değil
- Audit trail bazı işlemlerde eksik
- Transaction yönetimi dağınık
- Test edilebilirlik düşük

### Çözüm: Single Write Authority Pattern

```php
// app/Services/Ilan/IlanCrudService.php
namespace App\Services\Ilan;

use App\Models\Ilan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IlanCrudService
{
    /**
     * Tek yetkili yazma metodu
     */
    public function create(array $validatedData): Ilan
    {
        return DB::transaction(function () use ($validatedData) {
            // 1. Business rule validation
            $this->validateBusinessRules($validatedData);

            // 2. Data transformation
            $data = $this->transformForCreate($validatedData);

            // 3. Create
            $ilan = Ilan::create($data);

            // 4. Post-create hooks
            $this->afterCreate($ilan);

            // 5. Audit log
            Log::info('Ilan created', ['ilan_id' => $ilan->id]);

            return $ilan;
        });
    }

    public function update(Ilan $ilan, array $validatedData): Ilan
    {
        return DB::transaction(function () use ($ilan, $validatedData) {
            // Business logic burada
            $ilan->update($this->transformForUpdate($validatedData));
            $this->afterUpdate($ilan);
            return $ilan->fresh();
        });
    }
}
```

### Controller'da Kullanım

```php
// app/Http/Controllers/Admin/IlanController.php
class IlanController extends Controller
{
    public function __construct(
        private IlanCrudService $crudService
    ) {}

    public function store(IlanStoreRequest $request)
    {
        // Controller sadece delegate eder
        $ilan = $this->crudService->create($request->validated());

        return redirect()
            ->route('admin.ilanlar.show', $ilan)
            ->with('success', 'İlan oluşturuldu');
    }
}
```

### Kazanımlar

1. **Tek Gerçek Kaynağı**: Tüm yazma işlemleri tek yerden geçer
2. **Business Logic Garantisi**: Hiçbir yazma işlemi business rule'ları bypass edemez
3. **Transaction Yönetimi**: Tüm yazma işlemleri transaction içinde
4. **Test Edilebilirlik**: Service mock'lanabilir, controller testleri basit
5. **Audit Trail**: Her yazma işlemi otomatik loglanır
6. **Refactoring Kolaylığı**: Business logic değişikliği tek yerden yapılır

### Gerçek Dünya Senaryosu

```php
// Senaryo: İlan yayın durumu değiştiğinde bildirim gönder

// ❌ ÖNCE: Her controller'da tekrar kod
public function publish($id) {
    $ilan = Ilan::findOrFail($id);
    $ilan->yayin_durumu = 'yayinda';
    $ilan->save();
    // Bildirim göndermeyi unutmuşuz! 🐛
}

// ✅ SONRA: Tek yerden kontrol
// IlanCrudService::update() içinde
protected function afterUpdate(Ilan $ilan)
{
    if ($ilan->wasChanged('yayin_durumu') && $ilan->yayin_durumu === 'yayinda') {
        event(new IlanPublished($ilan));
    }
}

// Artık her update işleminde otomatik çalışır
```

---

## 3. Context7 Kanonik İsimlendirme Mantığı

### Sorun: Çok Dilli Proje Karmaşası

Yalıhan 2026 Türkçe-İngilizce hibrit bir projedir:

```php
// Gerçek kod örnekleri (tutarsız)
$ilan->status           // İngilizce
$ilan->aktif            // Türkçe
$ilan->yayin_durumu     // Türkçe
$ilan->is_active        // İngilizce
$ilan->durum            // Türkçe (belirsiz)
```

**Sorunlar:**
- Aynı kavram için 5 farklı isim
- Yeni geliştirici hangisini kullanacağını bilmiyor
- Refactoring imkansız (hangi alan hangisine karşılık geliyor?)
- AI asistanlar tutarsız kod üretiyor

### Çözüm: Context7 Kanonik Sözlük

Context7, her kavram için **tek bir kanonik isim** tanımlar:

```markdown
| Kavram | ❌ Yasak Varyantlar | ✅ Kanonik İsim |
|--------|---------------------|-----------------|
| Yayın durumu | status, durum, state | yayin_durumu |
| Aktiflik | active, is_active, aktif | aktiflik_durumu |
| Sıralama | order, sort_order, siralama | display_order |
| Öne çıkan | featured, is_featured, vitrin | one_cikan |
| Kapak görseli | featured_image, image, gorsel | kapak_resmi |
| Enlem | latitude, enlem | lat |
| Boylam | longitude, boylam | lng |
| Şehir | city, sehir | il / il_adi |
```

### Uygulama: Yalıhan Bekçi AST Taraması

```php
// yalihan-bekci/src/Scanners/Context7Scanner.php
class Context7Scanner
{
    private array $bannedTerms = [
        'status' => 'yayin_durumu',
        'active' => 'aktiflik_durumu',
        'is_active' => 'aktiflik_durumu',
        'featured' => 'one_cikan',
        // ...
    ];

    public function scan(string $filePath): array
    {
        $violations = [];
        $content = file_get_contents($filePath);

        foreach ($this->bannedTerms as $banned => $canonical) {
            if (preg_match("/['\"]$banned['\"]/", $content)) {
                $violations[] = [
                    'file' => $filePath,
                    'banned' => $banned,
                    'canonical' => $canonical,
                    'message' => "Use '$canonical' instead of '$banned'",
                ];
            }
        }

        return $violations;
    }
}
```

### Bypass Mekanizması

Bazı durumlarda legacy API uyumu için bypass gerekir:

```php
// ✅ Meşru bypass
$data['status'] = $ilan->yayin_durumu; // context7-ignore
return response()->json($data); // External API uyumu
```

### Kazanımlar

1. **Tutarlılık**: Tüm kod tabanında aynı kavram aynı isimle
2. **Öğrenilebilirlik**: Yeni geliştirici tek bir isim öğrenir
3. **Refactoring Güvenliği**: Kanonik isim değişirse, tüm kod tabanı güncellenir
4. **AI Uyumu**: AI asistanlar tutarlı kod üretir
5. **Dokümantasyon**: Tek bir terim, tek bir dokümantasyon

---

## 4. Antigravity Araçları Neden Git-Diff Tabanlı?

### Sorun: Tam Proje Taraması Performans Sorunu

Yalıhan 2026 büyük bir Laravel projesidir:

```bash
$ find app -name "*.php" | wc -l
1247

$ time php artisan bekci:audit --all
# 45 saniye (tüm dosyaları tarar)
```

**CI/CD'de sorun:**
- Her commit'te 45 saniye beklemek kabul edilemez
- Geliştiriciler commit öncesi kontrol yapmaktan kaçınır
- Pre-commit hook çok yavaş olduğu için devre dışı bırakılır

### Çözüm: Git-Diff Tabanlı İnkremental Tarama

```bash
#!/bin/bash
# scripts/tools/antigravity-preflight.sh

# Sadece değişen dosyaları tara
CHANGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -z "$CHANGED_FILES" ]; then
    echo "✅ No PHP files changed"
    exit 0
fi

# Sadece değişen dosyalarda Context7 kontrolü
for file in $CHANGED_FILES; do
    if grep -E "(status|active|featured)" "$file" > /dev/null; then
        echo "❌ Context7 violation in $file"
        exit 1
    fi
done

echo "✅ All checks passed ($(echo "$CHANGED_FILES" | wc -l) files)"
```

### Performans Karşılaştırması

```bash
# Tam tarama
$ time ./scripts/tools/antigravity-full-gate.sh
# 45 saniye

# Git-diff tabanlı
$ time ./scripts/tools/antigravity-preflight.sh
# 0.8 saniye (56x daha hızlı)
```

### Pre-Commit Hook Entegrasyonu

```bash
# .githooks/pre-commit
#!/bin/bash

# Hızlı kontroller (git-diff tabanlı)
./scripts/tools/antigravity-preflight.sh || exit 1

# Sadece değişen dosyalarda PHPStan
git diff --cached --name-only --diff-filter=ACM | grep '\.php$' | \
    xargs vendor/bin/phpstan analyse --level=5 || exit 1

echo "✅ Pre-commit checks passed"
```

### Kazanımlar

1. **Hız**: 56x daha hızlı (45s → 0.8s)
2. **Geliştirici Deneyimi**: Pre-commit hook artık tolere edilebilir
3. **CI/CD Verimliliği**: Her commit'te hızlı feedback
4. **Odaklanma**: Sadece değişen kod kontrol edilir
5. **Ölçeklenebilirlik**: Proje büyüdükçe tarama süresi artmaz

---

## 5. SAB Yönetişim Modeli Nasıl Çalışır?

### Sorun: Mimari Erozyonu

Büyük projelerde zaman içinde mimari kurallar erozyona uğrar:

```php
// İlk gün: Temiz mimari
class IlanController {
    public function store(IlanStoreRequest $request) {
        return $this->crudService->create($request->validated());
    }
}

// 6 ay sonra: Mimari erozyonu
class IlanController {
    public function store(Request $request) {
        // "Acil" bir özellik için validation bypass
        $ilan = Ilan::create($request->all()); // ❌

        // "Geçici" bir fix için business logic controller'da
        if ($request->has('urgent')) {
            $ilan->yayin_durumu = 'yayinda';
            $ilan->save();
        }

        return response()->json($ilan);
    }
}
```

### Çözüm: SAB (Structural Authority Boundary) Yönetişim Modeli

SAB, mimari kuralları **kod olarak** tanımlar ve **otomatik** enforce eder.

#### 1. Authority Tanımı (`.sab/authority.json`)

```json
{
  "write_authorities": {
    "ilan": {
      "service": "App\\Services\\Ilan\\IlanCrudService",
      "methods": ["create", "update", "delete"],
      "enforcement": "strict"
    }
  },
  "read_authorities": {
    "ilan": {
      "repository": "App\\Repositories\\IlanRepository",
      "enforcement": "warn"
    }
  },
  "naming_canon": {
    "status": "yayin_durumu",
    "active": "aktiflik_durumu"
  }
}
```

#### 2. AST Tarayıcı (Yalıhan Bekçi)

```php
// yalihan-bekci/src/Scanners/WriteAuthorityScanner.php
class WriteAuthorityScanner
{
    public function scanFile(string $filePath): array
    {
        $ast = $this->parsePhp($filePath);
        $violations = [];

        foreach ($ast->getNodes() as $node) {
            // Eloquent yazma metodlarını tespit et
            if ($this->isEloquentWrite($node)) {
                $model = $this->extractModel($node);

                // Authority kontrolü
                if (!$this->isAuthorizedWrite($filePath, $model)) {
                    $violations[] = [
                        'file' => $filePath,
                        'line' => $node->getLine(),
                        'model' => $model,
                        'message' => "Unauthorized write to $model. Use IlanCrudService.",
                    ];
                }
            }
        }

        return $violations;
    }

    private function isEloquentWrite($node): bool
    {
        return in_array($node->name, ['create', 'update', 'save', 'delete']);
    }
}
```

#### 3. CI/CD Entegrasyonu

```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  sab-governance:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: SAB Integrity Scan
        run: php artisan sab:integrity-scan

      - name: Bekçi Audit
        run: php artisan bekci:audit --all

      - name: Block on Violations
        run: |
          if [ -f "reports/sab-violations.json" ]; then
            echo "❌ SAB violations detected"
            cat reports/sab-violations.json
            exit 1
          fi
```

#### 4. Pre-Commit Hook

```bash
# .githooks/pre-commit
#!/bin/bash

# Hızlı SAB kontrolü (sadece değişen dosyalar)
./scripts/tools/antigravity-preflight.sh || {
    echo ""
    echo "❌ SAB violations detected. Fix them or use:"
    echo "   git commit --no-verify  (not recommended)"
    exit 1
}
```

### SAB Yönetişim Katmanları

```
┌─────────────────────────────────────────┐
│  1. İnsan (Kullanıcı)                   │ ← Mutlak otorite
├─────────────────────────────────────────┤
│  2. Canlı Kod & DB Şeması               │ ← Runtime gerçeği
├─────────────────────────────────────────┤
│  3. .sab/authority.json                 │ ← Yönetişim SSOT
├─────────────────────────────────────────┤
│  4. Yalıhan Bekçi (AST Tarayıcı)        │ ← Statik analiz
├─────────────────────────────────────────┤
│  5. CI/CD Gates                         │ ← Otomatik blocker
├─────────────────────────────────────────┤
│  6. Pre-Commit Hooks                    │ ← Geliştirici feedback
└─────────────────────────────────────────┘
```

### Kazanımlar

1. **Mimari Erozyonu Önleme**: Kurallar otomatik enforce edilir
2. **Dokümantasyon = Kod**: `.sab/authority.json` hem doküman hem enforcer
3. **Hızlı Feedback**: Pre-commit hook ile anında uyarı
4. **CI/CD Güvenliği**: Kural ihlali merge edilemez
5. **Öğrenilebilirlik**: Yeni geliştirici kuralları kod üzerinden öğrenir
6. **Refactoring Güvenliği**: Mimari değişiklik tüm kod tabanına yansır

### Gerçek Dünya Örneği

```bash
# Geliştirici yanlışlıkla controller'da yazma yapar
$ git add app/Http/Controllers/Admin/IlanController.php
$ git commit -m "Add urgent feature"

# Pre-commit hook devreye girer
❌ SAB violation detected:
   File: app/Http/Controllers/Admin/IlanController.php:45
   Issue: Unauthorized write to Ilan model
   Fix: Use IlanCrudService::create() instead of Ilan::create()

# Geliştirici düzeltir
$ vim app/Http/Controllers/Admin/IlanController.php
# Ilan::create() → $this->crudService->create()

$ git add app/Http/Controllers/Admin/IlanController.php
$ git commit -m "Add urgent feature (SAB compliant)"
✅ All checks passed
```

---

## 🎯 Sonuç

Bu 5 mimari karar Yalıhan 2026'nın temelini oluşturur:

1. **BaseModel**: Tüm modellerde tutarlı davranış
2. **IlanCrudService**: Tek yazma otoritesi
3. **Context7**: Kanonik isimlendirme
4. **Antigravity**: Git-diff tabanlı hızlı kontroller
5. **SAB**: Otomatik mimari yönetişim

Bu kararlar birlikte:
- ✅ Mimari erozyonunu önler
- ✅ Geliştirici deneyimini iyileştirir
- ✅ CI/CD verimliliğini artırır
- ✅ Refactoring güvenliğini sağlar
- ✅ Yeni geliştiricilerin öğrenme eğrisini düzleştirir

---

**Sürüm:** 1.0
**Son Güncelleme:** 2026-05-23
**Yazar:** Yalıhan AI OS (Oturum 25+)
