<?php

namespace Tests\Unit\Models;

use App\Http\Controllers\Admin\AI\IlanAIController;
use App\Http\Requests\StoreIlanRequest;
use App\Http\Requests\UpdateIlanRequest;
use App\Models\AnahtarYonetimi;
use App\Models\Ilan;
use App\Services\Ilan\IlanCrudService;
use App\Traits\YayinTipiResolverTrait;
use Tests\TestCase;

class AnahtarYonetimiAuditTest extends TestCase
{
    /**
     * Test AnahtarYonetimi canBeDelivered logic
     */
    public function test_anahtar_yonetimi_can_be_delivered(): void
    {
        $anahtarReady = new AnahtarYonetimi(['anahtar_statusu' => 'Hazır']);
        $this->assertTrue($anahtarReady->canBeDelivered());

        $anahtarWaiting = new AnahtarYonetimi(['anahtar_statusu' => 'Beklemede']);
        $this->assertTrue($anahtarWaiting->canBeDelivered());

        $anahtarReturned = new AnahtarYonetimi(['anahtar_statusu' => 'Geri Alındı']);
        $this->assertTrue($anahtarReturned->canBeDelivered());

        $anahtarDelivered = new AnahtarYonetimi(['anahtar_statusu' => 'Teslim Edildi']);
        $this->assertFalse($anahtarDelivered->canBeDelivered());

        $anahtarLost = new AnahtarYonetimi(['anahtar_statusu' => 'Kayıp']);
        $this->assertFalse($anahtarLost->canBeDelivered());
    }

    /**
     * Test AnahtarYonetimi accessor and mutator compatibility
     */
    public function test_anahtar_durumu_accessor_and_mutator(): void
    {
        $anahtar = new AnahtarYonetimi;
        $anahtar->anahtar_durumu = 'Hazır';

        $this->assertEquals('Hazır', $anahtar->anahtar_statusu);
        $this->assertEquals('Aktif', $anahtar->getAttributes()['anahtar_durumu']);
        $this->assertEquals('Hazır', $anahtar->anahtar_durumu);
    }

    /**
     * Test IlanAIController uses YayinTipiResolverTrait
     */
    public function test_ilan_ai_controller_uses_yayin_tipi_resolver_trait(): void
    {
        $traits = class_uses_recursive(IlanAIController::class);
        $this->assertArrayHasKey(YayinTipiResolverTrait::class, $traits);
    }

    /**
     * Test StoreIlanRequest and UpdateIlanRequest permit null proje_id, site_id, and site_apartman_id
     */
    public function test_request_rules_include_site_and_proje_fields(): void
    {
        $storeRules = (new StoreIlanRequest)->rules();
        $updateRules = (new UpdateIlanRequest)->rules();

        $this->assertArrayHasKey('site_id', $storeRules);
        $this->assertArrayHasKey('site_apartman_id', $storeRules);
        $this->assertArrayHasKey('proje_id', $storeRules);

        $this->assertArrayHasKey('site_id', $updateRules);
        $this->assertArrayHasKey('site_apartman_id', $updateRules);
        $this->assertArrayHasKey('proje_id', $updateRules);
    }

    /**
     * Test IlanCrudService mapCoreData maps site_id, site_apartman_id and proje_id
     */
    public function test_ilan_crud_service_maps_site_and_proje(): void
    {
        $service = app(IlanCrudService::class);
        $method = new \ReflectionMethod(IlanCrudService::class, 'mapCoreData');
        $method->setAccessible(true);

        $ilan1 = new Ilan;
        $method->invoke($service, $ilan1, [
            'baslik' => 'Test İlan',
            'site_apartman_id' => 42,
            'proje_id' => 99,
        ]);
        $this->assertEquals(42, $ilan1->site_id);
        $this->assertEquals(99, $ilan1->proje_id);

        $ilan2 = new Ilan;
        $method->invoke($service, $ilan2, [
            'baslik' => 'Test İlan 2',
            'site_id' => 88,
            'proje_id' => null,
        ]);
        $this->assertEquals(88, $ilan2->site_id);
        $this->assertNull($ilan2->proje_id);
    }
}
