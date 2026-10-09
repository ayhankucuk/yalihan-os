# Design Contract: MAIL-500 — Booking Request Email Template

**Tarih:** 2026-10-08
**Tasarımcı:** YALIHAN TASARIMCI
**Status:** PENDING_APPROVAL
**Priority:** HIGH (Bug fix)
**Complexity:** LOW

---

## Evidence

### FACT (Missing View)

```
View: emails.booking-request
Status: NOT FOUND (MAIL-500)
Referenced by: app/Mail/BookingRequestMail.php:51
```

### FACT (BookingRequestMail Data)

```php
// app/Mail/BookingRequestMail.php
class BookingRequestMail extends Mailable
{
    public array $bookingData;
    public $villa;

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-request',
            with: [
                'villa' => $this->villa,
                'booking' => $this->bookingData,
            ],
        );
    }
}
```

### FACT (BookingData Structure)

```php
array $bookingData = [
    'ad_soyad' => string,        // Guest name
    'telefon' => string,         // Guest phone
    'email' => string,           // Guest email
    'giristarihi' => string,     // Check-in date
    'cikistarihi' => string,     // Check-out date
    'kisi_sayisi' => int,       // Number of guests
    'mesaj' => string,           // Guest message (optional)
    'created_at' => datetime,    // Request timestamp
];
```

### FACT (Villa Object)

```php
$villa = [
    'id' => int,
    'baslik' => string,         // Villa title
    'fiyat' => numeric,         // Price
    'para_birimi' => string,    // Currency (TRY, USD, EUR)
    'il' => string,             // City
    'ilce' => string,           // District
    'danisman' => object|null,   // Assigned consultant
];
```

### FACT (ilan-created Template)

```blade
{{-- resources/views/emails/ilan-created.blade.php --}}
{{-- Design: Blue gradient header, white card, info rows --}}
{{-- Variables: $ilan (ilan model) --}}
```

### FACT (ilan-created Template Structure)

| Section | Content |
|---------|---------|
| Header | Blue gradient (#3b82f6 → #2563eb) with emoji icon |
| Title | Dynamic title (h2) |
| Info Rows | Label + Value pairs in grid |
| CTA Button | "İlanı Görüntüle" link |
| Footer | Copyright + timestamp |

---

## Karar

### Option 1: Create Dedicated Booking Request Template

**Design:** Adapt ilan-created structure for booking requests

**Pros:**
- Consistent with existing email design language
- Reuses proven layout patterns
- Clear separation of concerns

**Cons:**
- Duplicates similar markup
- Maintenance overhead (2 templates)

### Option 2: Reuse ilan-created with Conditional Sections

**Design:** Pass `$type` flag to ilan-created template

```blade
@if($type === 'booking')
    {{-- Booking-specific header --}}
    {{-- Booking-specific fields --}}
@else
    {{-- Original ilan-created layout --}}
@endif
```

**Pros:**
- Single template to maintain
- DRY principle

**Cons:**
- Template becomes complex
- Mixed concerns
- Harder to test

### RECOMMENDED: Option 1 (Dedicated Template)

**Rationale:**
- Email templates are relatively simple
- Clear separation aids debugging
- Booking and listing are different use cases
- Consistent with existing design system

---

## Email Template Design

### File Location

```
resources/views/emails/
├── booking-request.blade.php    (NEW)
```

### Visual Design

```
+------------------------------------------+
|  📅 Yeni Rezervasyon Talebi              |
|  [Blue gradient header]                  |
+------------------------------------------+
|                                          |
|  Villa Adı                               |
|  [Large title]                           |
|                                          |
|  +------------------------------------+  |
|  | Misafir:        Ahmet Yılmaz      |  |
|  | Telefon:        0532 123 45 67    |  |
|  | E-posta:        ahmet@mail.com    |  |
|  +------------------------------------+  |
|                                          |
|  +------------------------------------+  |
|  | Check-in:      15.10.2026          |  |
|  | Check-out:     20.10.2026          |  |
|  | Kişi Sayısı:   4 kişi             |  |
|  +------------------------------------+  |
|                                          |
|  [Mesaj]                                 |
|  "Tesis hakkında bilgi almak..."        |
|                                          |
|  [Görüntüle] [Kabul Et] [Reddet]       |
|                                          |
+------------------------------------------+
|  © 2026 Yalıhan Emlak                   |
+------------------------------------------+
```

### Color Palette

| Element | Color | Usage |
|---------|-------|-------|
| Primary | `#3b82f6` | Header gradient start |
| Primary Dark | `#2563eb` | Header gradient end |
| Text Primary | `#1f2937` | Titles, values |
| Text Secondary | `#6b7280` | Labels |
| Background | `#ffffff` | Content card |
| Border | `#e5e7eb` | Info row dividers |
| Success | `#10b981` | Accept button |
| Danger | `#ef4444` | Reject button |

### Typography

| Element | Font | Size | Weight |
|---------|------|------|--------|
| Header Title | System | 24px | 600 |
| Villa Title | System | 20px | 600 |
| Labels | System | 14px | 600 |
| Values | System | 14px | 400 |
| CTA Buttons | System | 16px | 600 |

---

## Component Specifications

### 1. Header

```blade
<div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 30px 20px; text-align: center;">
    <h1 style="margin: 0; font-size: 24px; font-weight: 600;">📅 Yeni Rezervasyon Talebi</h1>
</div>
```

### 2. Villa Info Card

```blade
<div style="padding: 20px;">
    <h2 style="font-size: 20px; color: #1f2937; margin: 0 0 15px 0; font-weight: 600;">
        {{ $villa->baslik }}
    </h2>

    @if($villa->il)
    <p style="color: #6b7280; margin: 0 0 10px 0; font-size: 14px;">
        📍 {{ $villa->il }}@if($villa->ilce), {{ $villa->ilce }}@endif
    </p>
    @endif

    @if($villa->fiyat)
    <p style="color: #1f2937; margin: 0; font-size: 16px; font-weight: 600;">
        💰 {{ number_format($villa->fiyat) }} {{ $villa->para_birimi ?? 'TRY' }}
    </p>
    @endif
</div>
```

### 3. Guest Info Grid

```blade
<div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin: 15px;">
    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
        <span style="font-weight: 600; color: #6b7280; width: 100px;">Misafir:</span>
        <span style="color: #1f2937;">{{ $booking['ad_soyad'] ?? 'N/A' }}</span>
    </div>
    <div style="display: flex; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
        <span style="font-weight: 600; color: #6b7280; width: 100px;">Telefon:</span>
        <span style="color: #1f2937;">{{ $booking['telefon'] ?? 'N/A' }}</span>
    </div>
    <div style="display: flex; padding: 10px 0;">
        <span style="font-weight: 600; color: #6b7280; width: 100px;">E-posta:</span>
        <span style="color: #1f2937;">{{ $booking['email'] ?? 'N/A' }}</span>
    </div>
</div>
```

### 4. Date & Guest Count

```blade
<div style="display: flex; background: #f9fafb; border-radius: 8px; padding: 20px; margin: 15px;">
    <div style="flex: 1; text-align: center;">
        <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 12px;">GİRİŞ</p>
        <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['giristarihi'] ?? 'N/A' }}</p>
    </div>
    <div style="flex: 1; text-align: center;">
        <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 12px;">ÇIKIŞ</p>
        <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['cikistarihi'] ?? 'N/A' }}</p>
    </div>
    <div style="flex: 1; text-align: center;">
        <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 12px;">KİŞİ</p>
        <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['kisi_sayisi'] ?? 'N/A' }}</p>
    </div>
</div>
```

### 5. Message (Optional)

```blade
@if(!empty($booking['mesaj']))
<div style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 15px; margin: 15px 0;">
    <p style="color: #92400e; font-size: 12px; font-weight: 600; margin: 0 0 10px 0;">💬 MESAJ</p>
    <p style="color: #1f2937; margin: 0; font-size: 14px;">{{ $booking['mesaj'] }}</p>
</div>
@endif
```

### 6. Action Buttons

```blade
<div style="padding: 20px; text-align: center;">
    <a href="{{ route('admin.bookings.show', $booking['id'] ?? 0) }}"
       style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px;">
        Görüntüle
    </a>
    <a href="{{ route('admin.bookings.accept', $booking['id'] ?? 0) }}"
       style="display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px;">
        Kabul Et
    </a>
    <a href="{{ route('admin.bookings.reject', $booking['id'] ?? 0) }}"
       style="display: inline-block; background: #ef4444; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 0 5px;">
        Reddet
    </a>
</div>
```

---

## Complete Template

```blade
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Rezervasyon Talebi</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background: #f3f4f6; padding: 20px; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">

        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; padding: 30px 20px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">📅 Yeni Rezervasyon Talebi</h1>
        </div>

        {{-- Villa Info --}}
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
            <h2 style="font-size: 20px; color: #1f2937; margin: 0 0 10px 0; font-weight: 600;">
                {{ $villa->baslik ?? 'Villa' }}
            </h2>
            @if($villa->il)
            <p style="color: #6b7280; margin: 0 0 8px 0; font-size: 14px;">
                📍 {{ $villa->il }}@if($villa->ilce), {{ $villa->ilce }}@endif
            </p>
            @endif
            @if($villa->fiyat)
            <p style="color: #1f2937; margin: 0; font-size: 16px; font-weight: 600;">
                💰 {{ number_format($villa->fiyat) }} {{ $villa->para_birimi ?? 'TRY' }}
            </p>
            @endif
        </div>

        {{-- Guest Info --}}
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="color: #6b7280; font-size: 12px; font-weight: 600; margin: 0 0 15px 0; text-transform: uppercase;">Misafir Bilgileri</h3>

            <div style="display: flex; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="font-weight: 600; color: #6b7280; width: 100px;">Ad Soyad:</span>
                <span style="color: #1f2937;">{{ $booking['ad_soyad'] ?? 'N/A' }}</span>
            </div>
            <div style="display: flex; padding: 10px 0; border-bottom: 1px solid #e5e7eb;">
                <span style="font-weight: 600; color: #6b7280; width: 100px;">Telefon:</span>
                <span style="color: #1f2937;">{{ $booking['telefon'] ?? 'N/A' }}</span>
            </div>
            <div style="display: flex; padding: 10px 0;">
                <span style="font-weight: 600; color: #6b7280; width: 100px;">E-posta:</span>
                <span style="color: #1f2937;">{{ $booking['email'] ?? 'N/A' }}</span>
            </div>
        </div>

        {{-- Date Range --}}
        <div style="display: flex; padding: 20px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
            <div style="flex: 1; text-align: center;">
                <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 11px; text-transform: uppercase;">Giriş</p>
                <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['giristarihi'] ?? 'N/A' }}</p>
            </div>
            <div style="flex: 1; text-align: center;">
                <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 11px; text-transform: uppercase;">Çıkış</p>
                <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['cikistarihi'] ?? 'N/A' }}</p>
            </div>
            <div style="flex: 1; text-align: center;">
                <p style="color: #6b7280; margin: 0 0 5px 0; font-size: 11px; text-transform: uppercase;">Kişi</p>
                <p style="color: #1f2937; margin: 0; font-weight: 600;">{{ $booking['kisi_sayisi'] ?? 'N/A' }}</p>
            </div>
        </div>

        {{-- Message (Optional) --}}
        @if(!empty($booking['mesaj']))
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
            <p style="color: #92400e; font-size: 12px; font-weight: 600; margin: 0 0 10px 0;">💬 Mesaj</p>
            <p style="color: #1f2937; margin: 0; font-size: 14px; line-height: 1.6;">{{ $booking['mesaj'] }}</p>
        </div>
        @endif

        {{-- Actions --}}
        <div style="padding: 25px 20px; text-align: center;">
            <a href="{{ route('admin.bookings.show', $booking['id'] ?? 0) }}"
               style="display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
                Görüntüle
            </a>
        </div>

        {{-- Footer --}}
        <div style="padding: 20px; text-align: center; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0;">© {{ date('Y') }} Yalıhan Emlak - Otomatik Bildirim Sistemi</p>
        </div>
    </div>
</body>
</html>
```

---

## Scope

### Files to CREATE

```
resources/views/emails/booking-request.blade.php
```

### Variables Required

| Variable | Type | Required | Description |
|----------|------|----------|-------------|
| `$villa` | Model | Yes | Villa object with baslik, fiyat, il, ilce |
| `$booking` | Array | Yes | Booking data (ad_soyad, telefon, email, giristarihi, cikistarihi, kisi_sayisi, mesaj) |
| `$booking['id']` | int | No | For action URLs |

### Routes Required (Backend)

```
admin.bookings.show    (GET)
admin.bookings.accept  (POST)
admin.bookings.reject (POST)
```

**Note:** Route existence is backend concern. If routes don't exist, action buttons may 404.

---

## Alternatives Evaluated

### Alternative A: Reuse ilan-created Template

**Verdict:** NOT RECOMMENDED

| Reason | Explanation |
|--------|-------------|
| Different purpose | Listing notification vs booking request |
| Different data | $ilan vs $villa + $booking arrays |
| Different actions | View link vs Accept/Reject buttons |
| Different tone | "New listing" vs "Booking request" |

### Alternative B: Plain Text Email

**Verdict:** NOT ACCEPTABLE

- Poor user experience
- Doesn't match brand identity
- No action buttons

---

## Acceptance Criteria

| ID | Criteria | Verification |
|----|----------|--------------|
| AC1 | Template renders without errors | Load in browser |
| AC2 | All required variables used correctly | Check `$villa->baslik`, `$booking['ad_soyad']` etc. |
| AC3 | Date formatting is consistent | Use `d.m.Y` format |
| AC4 | Action buttons are visible | Check buttons render |
| AC5 | Mobile responsive | Test at 375px width |
| AC6 | Fallback for missing optional fields | Handle null/empty gracefully |

---

## Estimated Effort

| Task | Complexity | Files |
|------|------------|-------|
| Create template | LOW | 1 |
| Test in browser | LOW | - |
| **TOTAL** | **LOW** | **1** |

---

## Related Issues

| Issue | Status |
|-------|--------|
| MAIL-500 | This contract addresses |

---

## Rollback Plan

1. Delete `booking-request.blade.php`
2. Mailable will throw ViewNotFoundException
3. Backend team creates temporary view

---

*YALIHAN TASARIMCI — MAIL-500 Design Contract v1.0*
