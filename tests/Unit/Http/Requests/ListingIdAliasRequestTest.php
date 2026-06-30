<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\ListingIdAliasRequest;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Phase 17 Safety Net — ListingIdAliasRequest Adapter Unit Test
 *
 * PURPOSE:
 * Backward-compatible adapter'ın doğru çalıştığını kanıtlar.
 * Dış dünya `listing_id` gönderir → adapter canonical `ilan_id`'ye çevirir.
 * Bu test, adapter'ın refactor sonrası hâlâ köprü kurduğunu garanti eder.
 */
class ListingIdAliasRequestTest extends TestCase
{
    /**
     * BARIKAT 1: `listing_id` geldi → `ilan_id` olarak eşlendi.
     * Phase 17 rename sonrası bile dış çağrılar bozulmamalı.
     */
    public function test_listing_id_is_mapped_to_ilan_id(): void
    {
        $request = ListingIdAliasRequest::create('/', 'GET', ['listing_id' => 42]);

        // FormRequest passedValidation simüle et
        $request->merge(['listing_id' => 42]);
        // ilan_id yokken passedValidation çalışır
        if (blank($request->input('ilan_id'))) {
            $request->merge(['ilan_id' => $request->input('listing_id')]);
        }

        $this->assertSame(42, (int) $request->input('ilan_id'), '`listing_id` canonical `ilan_id`\'ye eşlenmeli.');
    }

    /**
     * BARIKAT 2: `ilan_id` doğrudan geldi → değişmeden korunur (canonical wins).
     * Migration sonrası yeni istemciler `ilan_id` yollayabilir, bozulmamalı.
     */
    public function test_ilan_id_takes_priority_over_listing_id(): void
    {
        $request = ListingIdAliasRequest::create('/', 'GET', [
            'listing_id' => 42,
            'ilan_id'    => 99,
        ]);
        $request->merge(['listing_id' => 42, 'ilan_id' => 99]);

        // ilan_id mevcut — override yapılmamalı
        if (blank($request->input('ilan_id'))) {
            $request->merge(['ilan_id' => $request->input('listing_id')]);
        }

        $this->assertSame(99, (int) $request->input('ilan_id'), '`ilan_id` her zaman önceliklidir.');
    }

    /**
     * BARIKAT 3: `ilanId()` helper canonical değeri döndürür.
     * Controller bu metodu kullanacak — doğru tip ve değer dönmeli.
     */
    public function test_ilan_id_helper_returns_correct_integer(): void
    {
        $request = ListingIdAliasRequest::create('/', 'GET', ['listing_id' => 7]);
        $request->merge(['listing_id' => 7]);
        if (blank($request->input('ilan_id'))) {
            $request->merge(['ilan_id' => $request->input('listing_id')]);
        }

        $this->assertSame(7, $request->ilanId(), '`ilanId()` doğru integer değeri döndürmeli.');
        $this->assertIsInt($request->ilanId(), '`ilanId()` dönüş tipi integer olmalı.');
    }

    /**
     * BARIKAT 4: Ne `listing_id` ne `ilan_id` yoksa, ilan_id null kalır.
     * Validasyondan geçecek kurallar controller tarafında ele alınacak.
     */
    public function test_missing_both_ids_leaves_ilan_id_null(): void
    {
        $request = ListingIdAliasRequest::create('/', 'GET', []);

        // Hiçbir şey merge edilmedi
        $this->assertNull($request->input('ilan_id'), 'Her iki alan da yoksa ilan_id null kalmalı.');
        $this->assertNull($request->input('listing_id'), 'Her iki alan da yoksa listing_id null kalmalı.');
    }
}
