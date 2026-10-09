# Design Contract: İlanlar Index — Active Filter Chips

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** LOCAL_CLOSED

---

## Evidence

### FACT (Repository State)
- `resources/views/frontend/ilanlar/index.blade.php` — 428 lines
- Filter sidebar mevcut: location-tree, kategori, mulk_tipi, fiyat, oda, durum
- `$hasActiveFilters` değişkeni tanımlı (line 36)
- "Sıfırla" link'i mevcut (line 113-114)
- Form: `GET /ilanlar.index` — URL-based filter state

### FACT (Current UX Behavior)
- Kullanıcı dropdown seçer → form submit → page reload
- Aktif filtreler sadece dropdown'larda "selected" olarak görünür
- URL'de filtre parametreleri mevcut (örn: `?il[]=1&kategori_slug=satilik`)
- Tümünü sıfırla link'i mevcut

### REQUIREMENT
- Kullanıcı hangi filtrelerin aktif olduğunu kolayca görebilmeli
- Bireysel filtreleri kaldırabilmeli
- Tümünü sıfırlayabilmeli

---

## Karar

**DESIGN_DECISION:** Aktif filtreler "chip/tag" formatında results header'ında gösterilecek.

Her chip:
- Filtre adını gösterir (örn: "Bodrum", "Satılık", "2+1")
- X butonu ile bireysel kaldırma
- Tümünü temizle butonu

---

## Scope

**Etkilenecek Alan:**
- `resources/views/frontend/ilanlar/index.blade.php` (results header section)
- Mevcut `$hasActiveFilters` ve `selected_*` değişkenleri kullanılacak
- Backend değişikliği YOK

**Değişmeyecek:**
- Filter logic (controller, service)
- URL structure
- Form submission behavior
- Location tree component

---

## UI/UX Detayı

### Placement
Results header'ında, ilan sayısı ile birlikte:
```
┌──────────────────────────────────────────────────────────────┐
│  [İlan Sayısı] ilan bulundu                               │
│                                                              │
│  [Bodrum ×] [Satılık ×] [2+1 ×]              [Tümünü Temizle] │
└──────────────────────────────────────────────────────────────┘
```

### Chip Design
```
┌──────────────────┐
│ Bodrum    ✕     │  ← bg: neutral-100, text: neutral-700
└──────────────────┘     hover: bg: neutral-200

"Tümünü Temizle"  ← text: accent, underline on hover
```

### Responsive
- Desktop: Chips inline, overflow → wrap
- Mobile: Chips wrap, max 2-3 satır

---

## Acceptance Criteria

1. **AC1:** Aktif filtre yokken chip bar görünmez
2. **AC2:** Her aktif filtre için chip gösterilir
3. **AC3:** Chip X butonuna tıklayınca o filtre kaldırılır (URL güncellenir)
4. **AC4:** "Tümünü Temizle" tüm aktif filtreleri kaldırır
5. **AC5:** Chip bar responsive mobil'de düzgün wrap eder
6. **AC6:** Mevcut "Sıfırla" link'i ile tutarlı davranış

---

## Implementation Notları (Kodlayıcı için)

### Veri Hazırlığı
Blade'de mevcut değişkenler:
```php
$selectedIlIds, $selectedIlceIds, $selectedMahIds
$kategoriSlug
// ... diğer selected_* değişkenler
```

### Chip Üretimi İçin Örnek Logic
```php
@if($hasActiveFilters)
    <div class="active-filters">
        {{-- Konum chips --}}
        @foreach($selectedIlIds as $ilId)
            @php $il = $iller->find($ilId) @endphp
            @if($il)
                <a href="{{ removeFilter('il', $ilId) }}">{{ $il->name }} ×</a>
            @endif
        @endforeach

        {{-- Kategori chip --}}
        @if($kategoriSlug)
            <a href="{{ removeFilter('kategori_slug', $kategoriSlug) }}">{{ $kategoriSlug }} ×</a>
        @endif

        {{-- Tümünü temizle --}}
        <a href="{{ route('ilanlar.index') }}">Tümünü Temizle</a>
    @endif
```

### Helper Function
`removeFilter($key, $value)` — URL'den o parametreyi kaldırıp yeni URL döndürür.

---

## Technical Constraints

- Backend değişikliği YOK
- Sadece Blade template
- Mevcut CSS framework (Tailwind) kullanılacak
- JavaScript minimal — mümkünse pure HTML link

---

## Verification

- [ ] AC1: Empty state test
- [ ] AC2: Multi-filter test
- [ ] AC3: Individual remove test
- [ ] AC4: Clear all test
- [ ] AC5: Mobile responsive test
- [ ] AC6: Sıfırla link consistency test
