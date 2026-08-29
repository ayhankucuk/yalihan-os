<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\IlanPublicResource;
use App\Models\Il;
use App\Models\Ilan;
use App\Models\IlanKategori;
use App\Models\Ilce;
use App\Models\Mahalle;
use Tests\TestCase;

class IlanPublicResourceTest extends TestCase
{
    /** @test */
    public function it_uses_loaded_location_relations_when_legacy_columns_have_the_same_names(): void
    {
        $ilan = (new Ilan())->forceFill([
            'id' => 42,
            'il' => 'Legacy İl',
            'ilce' => 'Legacy İlçe',
            'mahalle' => 'Legacy Mahalle',
        ]);

        $ilan->setRelation('il', (new Il())->forceFill([
            'id' => 7,
            'il_adi' => 'Muğla',
        ]));
        $ilan->setRelation('ilce', (new Ilce())->forceFill([
            'id' => 8,
            'ilce_adi' => 'Bodrum',
        ]));
        $ilan->setRelation('mahalle', (new Mahalle())->forceFill([
            'id' => 9,
            'mahalle_adi' => 'Yalıkavak',
        ]));
        $ilan->setRelation('kategori', (new IlanKategori())->forceFill([
            'id' => 10,
            'name' => 'Villa',
        ]));

        $payload = (new IlanPublicResource($ilan))->resolve(request());

        $this->assertSame(['id' => 7, 'il_adi' => 'Muğla'], $payload['il']);
        $this->assertSame(['id' => 8, 'ilce_adi' => 'Bodrum'], $payload['ilce']);
        $this->assertSame(['id' => 9, 'mahalle_adi' => 'Yalıkavak'], $payload['mahalle']);
        $this->assertSame(['id' => 10, 'name' => 'Villa'], $payload['kategori']);
    }

    /** @test */
    public function it_returns_null_for_an_explicitly_loaded_missing_relation(): void
    {
        $ilan = (new Ilan())->forceFill(['id' => 42]);
        $ilan->setRelation('kategori', null);

        $payload = (new IlanPublicResource($ilan))->resolve(request());

        $this->assertArrayHasKey('kategori', $payload);
        $this->assertNull($payload['kategori']);
    }
}
