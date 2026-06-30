<?php

declare(strict_types=1);

namespace Tests\Feature\Calendar;

use App\Models\Ilan;
use App\Models\IlanCalendarFeed;
use App\Models\IlanReservation;
use App\Models\User;
use App\Services\Calendar\IlanCalendarIcsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * IlanCalendarFeed — Public ICS Feed Endpoint Testleri
 *
 * Kapsar:
 * - Geçerli token ile ICS döner (200)
 * - Geçersiz token 404 döner
 * - Revoke edilmiş feed 404 döner
 * - ICS içeriği RFC 5545 uyumlu başlık içerir
 * - ETag ile 304 döner
 * - IlanCalendarIcsService::getOrCreateFeed idempotent
 * - IlanCalendarIcsService::revokeFeed feed'i pasifleştirir
 */
class IlanCalendarFeedTest extends TestCase
{
    use RefreshDatabase;

    private Ilan $ilan;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->ilan = Ilan::factory()->create([
            'baslik' => 'Test Yazlık',
        ]);
    }

    // ── Public endpoint: token ile ICS ──

    /** @test */
    public function valid_token_returns_200_with_ics_content(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('END:VCALENDAR', $response->getContent());
        $this->assertStringContainsString('VERSION:2.0', $response->getContent());
    }

    /** @test */
    public function invalid_token_returns_404(): void
    {
        $response = $this->get(route('calendar.feed', ['token' => 'invalid-token-xyz']));

        $response->assertStatus(404);
    }

    /** @test */
    public function revoked_feed_returns_404(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => false,
            'revoked_at'       => now(),
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(404);
    }

    /** @test */
    public function inactive_feed_without_revoke_returns_404(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => false,
            'created_by_user_id' => $this->user->id,
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(404);
    }

    /** @test */
    public function ics_contains_calendar_name_from_ilan_baslik(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(200);
        $this->assertStringContainsString('X-WR-CALNAME:Test Yazlık', $response->getContent());
    }

    /** @test */
    public function ics_contains_etag_header(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(200);
        $this->assertNotEmpty($response->headers->get('ETag'));
        $this->assertNotEmpty($response->headers->get('Last-Modified'));
        $this->assertStringContainsString('max-age=300', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    /** @test */
    public function etag_match_returns_304(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
        ]);

        // İlk istek — ETag al
        $first = $this->get(route('calendar.feed', ['token' => $feed->token]));
        $etag = $first->headers->get('ETag');

        // İkinci istek — If-None-Match ile
        $second = $this->withHeaders(['If-None-Match' => $etag])
            ->get(route('calendar.feed', ['token' => $feed->token]));

        $second->assertStatus(304);
    }

    // ── ICS içinde rezervasyon VEVENT ──

    /** @test */
    public function ics_contains_vevent_for_active_reservation(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
        ]);

        // property_reservations tablosuna doğrudan insert (factory yok)
        IlanReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => now()->addDays(5)->toDateString(),
            'end_date'          => now()->addDays(10)->toDateString(),
            'nights'            => 5,
            'guest_name'        => 'Test Misafir',
            'reservation_state' => 'confirmed',
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(200);
        $this->assertStringContainsString('BEGIN:VEVENT', $response->getContent());
        $this->assertStringContainsString('END:VEVENT', $response->getContent());
        $this->assertStringContainsString('DTSTART;VALUE=DATE:', $response->getContent());
        $this->assertStringContainsString('DTEND;VALUE=DATE:', $response->getContent());
    }

    /** @test */
    public function ics_has_no_vevent_for_cancelled_reservation(): void
    {
        $feed = IlanCalendarFeed::create([
            'ilan_id'          => $this->ilan->id,
            'token'            => Str::random(64),
            'aktiflik_durumu'  => true,
        ]);

        IlanReservation::create([
            'property_id'       => $this->ilan->id,
            'start_date'        => now()->addDays(5)->toDateString(),
            'end_date'          => now()->addDays(10)->toDateString(),
            'nights'            => 5,
            'guest_name'        => 'İptal Misafir',
            'reservation_state' => 'cancelled',
            'cancelled_at'      => now(),
        ]);

        $response = $this->get(route('calendar.feed', ['token' => $feed->token]));

        $response->assertStatus(200);
        $this->assertStringNotContainsString('BEGIN:VEVENT', $response->getContent());
    }

    // ── IlanCalendarIcsService unit davranışı ──

    /** @test */
    public function get_or_create_feed_is_idempotent(): void
    {
        $service = resolve(IlanCalendarIcsService::class);

        $feed1 = $service->getOrCreateFeed($this->ilan, $this->user);
        $feed2 = $service->getOrCreateFeed($this->ilan, $this->user);

        $this->assertEquals($feed1->id, $feed2->id);
        $this->assertEquals(1, IlanCalendarFeed::where('ilan_id', $this->ilan->id)->count());
    }

    /** @test */
    public function revoke_feed_sets_aktiflik_durumu_false(): void
    {
        $service = resolve(IlanCalendarIcsService::class);

        $feed = $service->getOrCreateFeed($this->ilan, $this->user);
        $this->assertTrue($feed->aktiflik_durumu);

        $service->revokeFeed($this->ilan, $this->user);

        $feed->refresh();
        $this->assertFalse($feed->aktiflik_durumu);
        $this->assertNotNull($feed->revoked_at);
    }

    /** @test */
    public function revoke_feed_creates_new_feed_on_next_get_or_create(): void
    {
        $service = resolve(IlanCalendarIcsService::class);

        $feed1 = $service->getOrCreateFeed($this->ilan, $this->user);
        $service->revokeFeed($this->ilan, $this->user);

        $feed2 = $service->getOrCreateFeed($this->ilan, $this->user);

        $this->assertNotEquals($feed1->id, $feed2->id);
        $this->assertTrue($feed2->aktiflik_durumu);
    }

    /** @test */
    public function build_ics_returns_valid_vcalendar_string(): void
    {
        $service = resolve(IlanCalendarIcsService::class);

        $ics = $service->buildIcsForIlan($this->ilan);

        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('PRODID:-//Yalihan//Calendar Feed//TR', $ics);
        $this->assertStringContainsString('METHOD:PUBLISH', $ics);
    }

    /** @test */
    public function get_ics_meta_returns_expected_keys(): void
    {
        $service = resolve(IlanCalendarIcsService::class);

        $meta = $service->getIcsMeta($this->ilan);

        $this->assertArrayHasKey('last_modified', $meta);
        $this->assertArrayHasKey('etag', $meta);
        $this->assertArrayHasKey('count', $meta);
        $this->assertIsString($meta['etag']);
        $this->assertIsInt($meta['count']);
    }
}
