<?php

namespace App\Services\Concierge;

/**
 * PropertyFactSheet — Canonical read-only property facts for Guest Concierge.
 *
 * GUEST_CONCIERGE Phase 1 — SAAB Session 134
 *
 * Design Principles:
 * - READ-ONLY: This DTO is constructed from Ilan + AccessCredentialService
 * - NO CREDENTIALS: Door codes, lockbox codes, smart lock codes are FORBIDDEN
 * - WiFi password is ALLOWED (public network, not an access credential)
 * - All facts are pre-extracted, not computed at LLM call time
 *
 * GC-D3: PropertyFactSheet canonical contract
 * GC-D8: Credential zero-context boundary
 *
 * FACTS PROVIDED (P1):
 *   wifi_ssid, wifi_password
 *   check_in_time, check_out_time
 *   parking_info, house_rules
 *   ilan_name, ilan_address (street level)
 *   oda_sayisi, bina_yasi, kat
 *   klima_kullanimi, havuz_bilgisi
 *
 * FACTS FORBIDDEN:
 *   door_code, lockbox_code, smart_lock_code
 *   kapı_kodu, anahtar_kutusu_kodu
 */
final readonly class PropertyFactSheet
{
    private function __construct(
        public int $ilanId,
        public int $tenantId,
        public ?int $reservationId,
        // ── Identity ─────────────────────────────────────────────
        public string $ilanAdi,
        public ?string $ilanAdresi,
        // ── Operation Times ──────────────────────────────────────
        public string $checkInTime,
        public string $checkOutTime,
        // ── WiFi (allowed — public network) ────────────────────
        public ?string $wifiSsid,
        public ?string $wifiPassword,
        // ── Property Info ────────────────────────────────────────
        public ?string $odaSayisi,
        public ?string $binaYasi,
        public ?string $kat,
        public ?string $parkingBilgisi,
        public ?string $havuzBilgisi,
        // ── House Rules ─────────────────────────────────────────
        public ?string $evKurallari,
        // ── Klima Kullanimi ────────────────────────────────────
        public ?string $klimaKullanimi,
        // ── Fact Map (for validation) ──────────────────────────
        public array $availableFactKeys,
    ) {}

    // ── Factory ──────────────────────────────────────────────────────

    /**
     * Build from Ilan model and reservation context.
     *
     * @param \App\Models\Ilan $ilan
     * @param int|null $reservationId
     * @param array $wifiCredentials WiFi SSID + password from AccessCredentialService
     */
    public static function build(
        \App\Models\Ilan $ilan,
        ?int $reservationId,
        array $wifiCredentials = [],
    ): self {
        $ilanAdi = $ilan->baslik ?? $ilan->title ?? "Mülk #{$ilan->id}";

        // WiFi facts (allowed — public network, not access credential)
        $wifiSsid = $wifiCredentials['ssid'] ?? null;
        $wifiPassword = $wifiCredentials['password'] ?? null;

        // Build available fact keys for validation
        // Note: parking_info, ev_kurallari, klima_kullanimi, havuz_bilgisi
        // are NOT yet in ilanlar schema — use metadata JSON or extend later
        $availableFactKeys = array_keys(array_filter([
            'wifi_ssid' => $wifiSsid,
            'wifi_password' => $wifiPassword,
            'check_in_time' => $ilan->check_in_time ?? '14:00',
            'check_out_time' => $ilan->check_out_time ?? '11:00',
            'parking_info' => null, // TODO: extend to ilan_turizm_details in P2
            'house_rules' => null, // TODO: extend to ilan_turizm_details in P2
            'klima_kullanimi' => null, // TODO: extend to ilan_turizm_details in P2
            'havuz_bilgisi' => null, // TODO: extend to ilan_turizm_details in P2
        ], fn($v) => $v !== null));

        return new self(
            ilanId: $ilan->id,
            tenantId: $ilan->tenant_id,
            reservationId: $reservationId,
            ilanAdi: $ilanAdi,
            ilanAdresi: $ilan->adres ?? null,
            checkInTime: $ilan->check_in_time ?? '14:00',
            checkOutTime: $ilan->check_out_time ?? '11:00',
            wifiSsid: $wifiSsid,
            wifiPassword: $wifiPassword,
            odaSayisi: $ilan->oda_sayisi ?? null,
            binaYasi: $ilan->bina_yasi ?? null,
            kat: $ilan->kat ?? null,
            parkingBilgisi: $ilan->parking_info ?? null,
            havuzBilgisi: $ilan->havuz_bilgisi ?? null,
            evKurallari: $ilan->ev_kurallari ?? null,
            klimaKullanimi: $ilan->klima_kullanimi ?? null,
            availableFactKeys: $availableFactKeys,
        );
    }

    /**
     * Build an empty fact sheet (no property context).
     */
    public static function empty(): self
    {
        return new self(
            ilanId: 0,
            tenantId: 0,
            reservationId: null,
            ilanAdi: '',
            ilanAdresi: null,
            checkInTime: '14:00',
            checkOutTime: '11:00',
            wifiSsid: null,
            wifiPassword: null,
            odaSayisi: null,
            binaYasi: null,
            kat: null,
            parkingBilgisi: null,
            havuzBilgisi: null,
            evKurallari: null,
            klimaKullanimi: null,
            availableFactKeys: [],
        );
    }

    // ── Fact Validation ─────────────────────────────────────────────

    /**
     * Check if all required facts are available.
     * Used by AuthorityPolicy to determine if answer is allowed.
     *
     * GC-D6: No Fact → No Answer → Escalate (application layer enforcement)
     */
    public function hasAllFacts(array $requiredFactKeys): bool
    {
        foreach ($requiredFactKeys as $key) {
            if (!in_array($key, $this->availableFactKeys, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get missing fact keys for a given requirement.
     */
    public function getMissingFacts(array $requiredFactKeys): array
    {
        $missing = [];
        foreach ($requiredFactKeys as $key) {
            if (!in_array($key, $this->availableFactKeys, true)) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    // ── Fact Access ────────────────────────────────────────────────

    /**
     * Get a specific fact value by key.
     */
    public function getFact(string $key): ?string
    {
        return match ($key) {
            'wifi_ssid' => $this->wifiSsid,
            'wifi_password' => $this->wifiPassword,
            'check_in_time' => $this->checkInTime,
            'check_out_time' => $this->checkOutTime,
            'parking_info' => $this->parkingBilgisi,
            'house_rules' => $this->evKurallari,
            'klima_kullanimi' => $this->klimaKullanimi,
            'havuz_bilgisi' => $this->havuzBilgisi,
            default => null,
        };
    }

    /**
     * Build a prompt context string with available facts.
     */
    public function toPromptContext(): string
    {
        $facts = [];

        if ($this->ilanAdi) {
            $facts[] = "Mülk: {$this->ilanAdi}";
        }
        if ($this->ilanAdresi) {
            $facts[] = "Adres: {$this->ilanAdresi}";
        }
        $facts[] = "Giriş saati: {$this->checkInTime}";
        $facts[] = "Çıkış saati: {$this->checkOutTime}";

        if ($this->wifiSsid && $this->wifiPassword) {
            $facts[] = "WiFi: {$this->wifiSsid} / Şifre: {$this->wifiPassword}";
        }
        if ($this->parkingBilgisi) {
            $facts[] = "Otopark: {$this->parkingBilgisi}";
        }
        if ($this->evKurallari) {
            $facts[] = "Ev kuralları: {$this->evKurallari}";
        }
        if ($this->klimaKullanimi) {
            $facts[] = "Klima: {$this->klimaKullanimi}";
        }
        if ($this->havuzBilgisi) {
            $facts[] = "Havuz: {$this->havuzBilgisi}";
        }

        return implode("\n", $facts);
    }
}
