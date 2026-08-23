<?php

namespace App\Enums;

/**
 * VccStatus — C5.1 / Booking.com Payment API VCC Wire Contract
 *
 * SAAB C5.1-D01 Recovery Baseline: 877f45d
 *
 * Booking.com resmi Payments API wire status değerleri:
 *   AVAILABLE        — Kart oluşturulmuş, henüz fonlanmamış; çekim yapılamaz
 *   NOT_LOADED       — Kart fonlanmamış; çekim yapılamaz
 *   FUNDED           — Kart fonlanmış; çekim yapılabilir
 *   PARTIALLY_CHARGED — Kart kısmen çekilmiş; kalan bakiye ayrı politika gerektirir
 *   FULLY_CHARGED   — Kart tamamen çekilmiş; terminal
 *   CANCELLED        — Kart iptal edilmiş; terminal
 *   UNKNOWN          — Bilinmeyen/parse edilemeyen değer; fail-safe normalize
 *
 * NORMALIZATION BOUNDARY (SAAB C5.1-D01 Invariant):
 *   Provider'dan gelen ham değer normalize edilmeden asla doğrudan saklanmaz.
 *   fromProviderStatus(string $rawValue): ?self
 *   Case-insensitive eşleştirme yapar; tanınmayan değer → null dönmez → UNKNOWN.
 *
 * Chargeability Semantiği (Booking.com terminolojisi):
 *   AVAILABLE      → false  (henüz aktive edilmedi)
 *   NOT_LOADED      → false  (fon yok)
 *   FUNDED          → true   (fon var, çekim yapılabilir)
 *   PARTIALLY_CHARGED → false (kalan bakiye için ayrı politika gerekli)
 *   FULLY_CHARGED  → false  (tamamen tüketildi, terminal)
 *   CANCELLED       → false  (iptal, terminal)
 *   UNKNOWN         → false  (fail-safe)
 *
 * Internal YALIHAN lifecycle state'leri (ACTIVE, EXPIRED, BLOCKED) bu enum'da DEĞİL.
 * Gerekirse ayrı internal enum tanımlanabilir; Booking.com wire contract'tan ayrı tutulur.
 */
enum VccStatus: string
{
    // ── Booking.com Wire Contract ──────────────────────────────────────
    case AVAILABLE         = 'available';
    case NOT_LOADED        = 'not_loaded';
    case FUNDED            = 'funded';
    case PARTIALLY_CHARGED = 'partially_charged';
    case FULLY_CHARGED     = 'fully_charged';
    case CANCELLED         = 'cancelled';
    case UNKNOWN           = 'unknown';

    // ────────────────────────────────────────────────────────────────────
    // Presentation
    // ────────────────────────────────────────────────────────────────────
    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE         => 'Mevcut',
            self::NOT_LOADED        => 'Yüklenmedi',
            self::FUNDED            => 'Fonlanmış',
            self::PARTIALLY_CHARGED => 'Kısmi Çekim',
            self::FULLY_CHARGED     => 'Tam Çekim',
            self::CANCELLED         => 'İptal',
            self::UNKNOWN           => 'Bilinmiyor',
        };
    }

    // ────────────────────────────────────────────────────────────────────
    // Chargeability (Booking.com semantics)
    // ────────────────────────────────────────────────────────────────────
    /**
     * Karttan yeni bir charge başlatılabilir mi?
     *
     * Booking.com semantics:
     *   AVAILABLE         → false (henüz aktive edilmedi)
     *   NOT_LOADED        → false (fon yok)
     *   FUNDED            → true  (fon var, çekim yapılabilir)
     *   PARTIALLY_CHARGED → false (kalan bakiye ayrı politika gerektirir)
     *   FULLY_CHARGED     → false (tamamen tüketildi)
     *   CANCELLED          → false (iptal)
     *   UNKNOWN            → false (fail-safe)
     */
    public function isChargeable(): bool
    {
        return $this === self::FUNDED;
    }

    // ────────────────────────────────────────────────────────────────────
    // Terminal State Detection
    // ────────────────────────────────────────────────────────────────────
    /**
     * VCC lifecycle'i bu durumda kalıcı olarak sonlanmış mıdır?
     *
     * Terminal states:
     *   FULLY_CHARGED — tüm fon tüketildi
     *   CANCELLED     — kart iptal edildi
     *   UNKNOWN       — bilinmeyen durum fail-safe terminal kabul edilir
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::FULLY_CHARGED,
            self::CANCELLED,
            self::UNKNOWN,
        ], true);
    }

    // ────────────────────────────────────────────────────────────────────
    // Normalization Boundary
    // ────────────────────────────────────────────────────────────────────
    /**
     * Booking.com provider ham wire değerini VccStatus enum'ına normalize eder.
     *
     * C5.1-D01 Invariant: fromProviderStatus hiçbir zaman null dönmez.
     * Tanınmayan değer → VccStatus::UNKNOWN (fail-safe).
     * Case-insensitive eşleştirme yapar.
     *
     * @param string|null $rawValue Provider'dan gelen ham status değeri
     * @return self Normalize edilmiş enum değeri; asla null değil
     */
    public static function fromProviderStatus(?string $rawValue): self
    {
        if ($rawValue === null || $rawValue === '') {
            return self::UNKNOWN;
        }

        // Case-insensitive lookup
        $normalized = strtolower(trim($rawValue));

        foreach (self::cases() as $case) {
            if ($case->value === $normalized) {
                return $case;
            }
        }

        // Unknown provider value → fail-safe UNKNOWN
        return self::UNKNOWN;
    }

    /**
     * Bu VCC hâlâ aktif (terminal değil) midir?
     */
    public function isActive(): bool
    {
        return !$this->isTerminal()
            && $this !== self::NOT_LOADED;
    }
}
