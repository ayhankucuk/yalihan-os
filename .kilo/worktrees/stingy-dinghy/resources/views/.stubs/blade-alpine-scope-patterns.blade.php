{{--
    TEST FILE: Blade @include + Alpine.js Scope Patterns
    Versiyon: 1.0.0
    Tarih: 2025-12-14
--}}

{{-- ====================================== --}}
{{-- PATTERN 1: ✅ DOĞRU - $parent Prefix --}}
{{-- ====================================== --}}

<div x-data="{
    selectedKategoriSlug: 'arsa',
    selectedYayinTipi: null,
    oyunYonetimToggle: false
}">
    <h2>İlan Formu - Kategori Seçimi</h2>

    {{-- Kategori seçim dropdown --}}
    <select x-model="selectedKategoriSlug" class="form-control">
        <option value="arsa">Arsa</option>
        <option value="konut">Konut</option>
        <option value="kiralik">Kiralık</option>
    </select>

    {{-- DOĞRU: @include içinde $parent prefix kullanımı --}}
    @include('admin.ilanlar.components.category-fields.arsa-fields')
    @include('admin.ilanlar.components.category-fields.konut-fields')
    @include('admin.ilanlar.components.category-fields.kiralik-fields')
</div>


{{-- ====================================== --}}
{{-- PATTERN 2: ✅ DOĞRU - Nested x-data --}}
{{-- ====================================== --}}

<div x-data="{
    parentVar: 'value',
    items: []
}">
    @include('components.child', [
        'childItems' => $items,
    ])

    {{-- Nested x-data ile $parent erişimi --}}
    <div x-data="childComponent()" class="nested">
        {{-- DOĞRU: Nested statusda da $parent kullanılmalı --}}
        <div x-show="$parent.parentVar === 'value'">
            Content is visible when parent has correct value
        </div>
    </div>
</div>


{{-- ====================================== --}}
{{-- PATTERN 3: ✅ DOĞRU - Multiple Conditions --}}
{{-- ====================================== --}}

<div x-data="{
    kategori: null,
    aktif: true,
    acikMi: false
}">
    {{-- Include edilen component --}}
    @include('components.dynamic-form')

    {{-- Component içerisinde (dynamic-form.blade.php) olması gereken: --}}
    {{-- <div x-show="$parent.kategori && $parent.aktif && $parent.acikMi"> --}}
</div>


{{-- ====================================== --}}
{{-- PATTERN 4: ✅ DOĞRU - Alpine Store (Alternatif) --}}
{{-- ====================================== --}}

<script>
    // Global store tanımı
    document.addEventListener('alpine:init', () => {
        Alpine.store('category', {
            selectedKategoriSlug: 'arsa',
            selectedYayinTipi: null
        });
    });
</script>

{{-- Store kullanımı (her yerde erişim) --}}
@include('components.form-fields')
{{--
    Form fields içinde:
    <div x-show="$store.category.selectedKategoriSlug === 'arsa'">
        Arsa fields
    </div>
--}}


{{-- ====================================== --}}
{{-- PATTERN 5: ✅ DOĞRU - Props Geçirme --}}
{{-- ====================================== --}}

@include('components.category-form', [
    'selectedCategory' => $initialCategory,
    'yayin_tipi' => $yayinTipi,
])

{{--
    Category form component'te (category-form.blade.php):

    <div x-data="{
        selectedCategory: '{{ $selectedCategory }}',
        yayin_tipi: '{{ $yayin_tipi }}'
    }">
        <!-- Form fields directly use local scope variables -->
    </div>
--}}


{{-- ====================================== --}}
{{-- PATTERN 6: ❌ YANLIŞ - $parent Prefix Eksik --}}
{{-- ====================================== --}}

{{--
    UYARI: Bu pattern'ler hatalıdır ve kontrol araçları tarafından tespit edilir!
    Aşağıda gösterilmek üzere yazılmıştır - gerçek kodda KULLANILAMAZ!
--}}

{{--
    ❌ YANLIŞ PATTERN 1: Direkt scope erişimi
    @include('components.dynamic-section')

    Component içinde (DİKKAT: HATALı - bu şekilde yazılmaz):
    <div x-show="$parent.selectedCategory">  <!-- HATA: selectedCategory undefined! -->
        Content
    </div>

    ✅ DÜZELTME:
    <div x-show="$parent.selectedCategory">  <!-- DOĞRU -->
        Content
    </div>
--}}

{{--
    ❌ YANLIŞ PATTERN 2: x-if ile hata
    Component içinde (DİKKAT: HATALı):
    <div x-if="$parent.isOpen">  <!-- HATA: isOpen undefined! -->
        Conditional content
    </div>

    ✅ DÜZELTME:
    <div x-if="$parent.isOpen">  <!-- DOĞRU -->
        Conditional content
    </div>
--}}


{{-- ====================================== --}}
{{-- PATTERN 7: ✅ DOĞRU - Blade @switch ile $parent --}}
{{-- ====================================== --}}

<div x-data="{ kategori: 'arsa' }">
    @switch($kategori)
        @case('arsa')
            @include('categories.arsa')
            {{-- Include içinde: x-show="$parent.kategori === 'arsa'" --}}
        @break

        @case('konut')
            @include('categories.konut')
            {{-- Include içinde: x-show="$parent.kategori === 'konut'" --}}
        @break
    @endswitch
</div>


{{-- ====================================== --}}
{{-- PATTERN 8: ✅ DOĞRU - Blade @foreach ile Loop Context --}}
{{-- ====================================== --}}

<div x-data="{
    items: @json($items),
    expandedId: null
}">
    @foreach ($items as $item)
        @include('item-card', ['item' => $item])
        {{--
            Include içinde:
            - Blade değişkenleri: {{ $item->name }}
            - Alpine scope: x-show="$parent.expandedId === {{ $item->id }}"
        --}}
    @endforeach
</div>


{{-- ====================================== --}}
{{-- VALIDATION & COMPLIANCE CHECKLIST --}}
{{-- ====================================== --}}

{{--
## ✅ Kontrol Listesi (Her @include için):

1. [ ] @include directive kullanılıyor mu?
   → find . -name "*.blade.php" -exec grep -l "@include" {} \;

2. [ ] Include edilen component'te x-show/x-if var mı?
   → grep -r "x-show\|x-if" resources/views/components/

3. [ ] Parent scope'ta ilgili değişken tanımı var mı?
   → x-data = { selectedKategoriSlug: 'arsa', ... }

4. [ ] $parent prefix kullanılıyor mu?
   → x-show="$parent.selectedKategoriSlug && ..."

5. [ ] Test yapılmış mı?
   → Kategori seçimi → Component görünürlüğü değişiyor mu?

## 🔧 Validation Komutları:

```bash
# Tüm workspace kontrol et
php artisan blade:validate-alpine-scope

# Belirli dosya kontrol et
php artisan blade:validate-alpine-scope --file=admin/ilanlar/components/category-fields/arsa-fields

# Strict mode (exit code 1 if errors)
php artisan blade:validate-alpine-scope --strict

# Bash script ile tarama
./scripts/validation/detect-blade-alpine-scope.sh

# Pre-commit hook çalıştır
./.githooks/pre-commit-blade-alpine
```

## 📚 Referans Dosyalar:

- Pattern Dokümantasyon: `.context7/ALPINE_JS_BLADE_INCLUDE_STANDARDS.md`
- Validasyon Komutu: `app/Console/Commands/ValidateBladeAlpineScope.php`
- Detection Script: `scripts/validation/detect-blade-alpine-scope.sh`
- Pre-commit Hook: `.githooks/pre-commit-blade-alpine`

--}}
