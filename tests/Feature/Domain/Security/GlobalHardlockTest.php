<?php

namespace Tests\Feature\Domain\Security;

use App\Domain\Core\Security\GlobalHardlockManager;
use App\Domain\Core\Security\GlobalHardlockRegistry;
use App\Domain\Core\Security\SignatureSealEngine;
use App\Domain\Ilan\IlanDomainYonetici;
use App\Domain\Kisi\KisiDomainYonetici;
use App\Exceptions\Governance\GlobalHardlockException;
use App\Domain\CQRS\Messaging\EventDispatcher;
use App\Services\Ilan\IlanCrudService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Class GlobalHardlockTest
 * @package Tests\Feature\Domain\Security
 * @description Phase 20: Gelişmiş imza mühürleme ve küresel hardlock mekanizmalarının entegrasyon test paketi.
 */
class GlobalHardlockTest extends TestCase
{
    use RefreshDatabase;

    private GlobalHardlockManager $manager;
    private SignatureSealEngine $sealEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new GlobalHardlockManager();
        $this->sealEngine = new SignatureSealEngine();

        // Her test öncesi Cache durumunu temizle
        Cache::forget('governance.system_compromised');
        Cache::forget('governance.compromised.SYSTEM');
        Cache::forget('governance.compromised.1');
        Cache::forget('governance.compromised.99');
    }

    /**
     * @test
     */
    public function it_successfully_generates_and_verifies_cryptographic_seals(): void
    {
        $payload = [
            'tenant_id' => 1,
            'amount' => 500,
            'details' => ['danisman_id' => 4]
        ];

        $seal = $this->sealEngine->generateSeal($payload, 'test_salt');
        $this->assertNotEmpty($seal);

        $isValid = $this->sealEngine->verifySeal($payload, $seal, 'test_salt');
        $this->assertTrue($isValid);

        // Değiştirilmiş payload bütünlük kontrolünde geçersiz olmalıdır
        $tamperedPayload = $payload;
        $tamperedPayload['amount'] = 501;
        $isInvalid = $this->sealEngine->verifySeal($tamperedPayload, $seal, 'test_salt');
        $this->assertFalse($isInvalid);
    }

    /**
     * @test
     */
    public function it_initiates_hardlock_correctly(): void
    {
        $this->assertFalse($this->manager->isHardlocked(1));

        // Kiracı bazlı kilitleme
        $this->manager->initiateHardlock(1, 'Tenant database leak detected');
        $this->assertTrue($this->manager->isHardlocked(1));

        // Sistem genel kilit durumu henüz pasif olmalı
        $this->assertFalse($this->manager->isHardlocked(0));

        // Sistem genel kilitleme
        $this->manager->initiateHardlock(0, 'System critical file mismatch');
        $this->assertTrue($this->manager->isHardlocked(0));
        $this->assertTrue($this->manager->isHardlocked(1)); // Genel kilit tüm kiracıları kilitlemeli
    }

    /**
     * @test
     */
    public function it_blocks_mutations_on_ilan_domain_yonetici_during_hardlock(): void
    {
        // 1. Kiracıyı kilitle
        $this->manager->initiateHardlock(1, 'Compromised');

        // 2. Mock yapılarını kur
        $crudMock = $this->createMock(IlanCrudService::class);
        $dispatcherMock = $this->createMock(EventDispatcher::class);
        $ilanYonetici = new IlanDomainYonetici($crudMock, $dispatcherMock, null, $this->manager);

        // 3. Aktif kullanıcıyı ayarla
        $user = new User();
        $user->tenant_id = 1;
        $this->actingAs($user);

        // 4. İstisna fırlatılmalı
        $this->expectException(GlobalHardlockException::class);
        $ilanYonetici->yayinDurumuMutasyonu(999, 'yayinda');
    }

    /**
     * @test
     */
    public function it_blocks_mutations_on_kisi_domain_yonetici_during_hardlock(): void
    {
        // 1. Kiracıyı kilitle
        $this->manager->initiateHardlock(1, 'Compromised');

        // 2. Mock yapılarını kur
        $dispatcherMock = $this->createMock(EventDispatcher::class);
        $kisiYonetici = new KisiDomainYonetici($dispatcherMock, null, $this->manager);

        // 3. İstisna fırlatılmalı
        $this->expectException(GlobalHardlockException::class);
        $kisiYonetici->secureLeadIngestion([
            'tenant_id' => 1,
            'ad_soyad' => 'Lock Test Lead'
        ]);
    }

    /**
     * @test
     */
    public function it_can_be_recovered_via_artisan_command(): void
    {
        // 1. Kiracıyı kilitle
        $this->manager->initiateHardlock(1, 'Compromised');
        $this->assertTrue($this->manager->isHardlocked(1));

        $token = config('yalihan.fortress_secure_salt.kripto_anahtar');

        // 2. Artisan komutuyla kilidi kaldır
        $exitCode = Artisan::call('governance:recover', [
            'tenant_id' => 1,
            '--token' => $token
        ]);

        $this->assertEquals(0, $exitCode);
        $this->assertFalse($this->manager->isHardlocked(1));
    }
}
