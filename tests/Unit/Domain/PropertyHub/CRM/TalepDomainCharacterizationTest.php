<?php

namespace Tests\Unit\Domain\PropertyHub\CRM;

use App\Domain\CRM\Contracts\TalepRepositoryInterface;
use App\Domain\CRM\DTOs\TalepCreateCommand;
use App\Domain\CRM\DTOs\TalepListCriteria;
use App\Domain\CRM\Services\CreateTalepUseCase;
use App\Domain\CRM\Services\ListTaleplerUseCase;
use App\Enums\TalepDurumu;
use App\Infrastructure\CRM\EloquentTalepRepositoryAdapter;
use App\Models\Kisi;
use App\Models\Talep;
use App\Models\User;
use App\Repositories\TalepRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Characterization tests for Talep Domain — Strangler Fig Migration
 *
 * Captures the existing behavior of:
 *   - TalepOrchestrator (list + form data + stats)
 *   - TalepAuthorityService (create + update + delete)
 *
 * These tests MUST pass before and after the domain refactor.
 * They serve as equivalence contracts between legacy and domain code.
 */
class TalepDomainCharacterizationTest extends TestCase
{
    use RefreshDatabase;

    private ListTaleplerUseCase $listUseCase;
    private CreateTalepUseCase  $createUseCase;
    private object             $admin; // Mockery mock

    protected function setUp(): void
    {
        parent::setUp();

        // Admin user: mocked to bypass Spatie role DB check
        // (same pattern as TalepRepositoryAuthorizationTest)
        $realAdmin = User::factory()->create();
        $this->admin = Mockery::mock($realAdmin)->makePartial();
        $this->admin->shouldReceive('isAdmin')->andReturn(true);
        $this->admin->shouldReceive('hasRole')->andReturn(true);
        $this->admin->shouldReceive('getAuthIdentifier')->andReturn($realAdmin->id);

        $this->actingAs($this->admin);

        // Bind interface → adapter (Strangler Fig: domain path)
        $this->app->instance(
            TalepRepositoryInterface::class,
            new EloquentTalepRepositoryAdapter(new TalepRepository(new Talep()))
        );

        $this->listUseCase   = $this->app->make(ListTaleplerUseCase::class);
        $this->createUseCase = $this->app->make(CreateTalepUseCase::class);
    }

    // ─── List Use Case Characterization ──────────────────────────────────────

    public function test_list_returns_paginated_talepler(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->count(3)->create(['kisi_id' => $kisi->id]);
        Talep::factory()->count(2)->create(['kisi_id' => $kisi->id, 'talep_durumu' => 'yayinda']);

        $result = $this->listUseCase->execute([]);

        $this->assertCount(5, $result);
    }

    public function test_list_paginates_correctly(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->count(15)->create(['kisi_id' => $kisi->id]);

        $result = $this->listUseCase->execute(['per_page' => 5]);

        $this->assertCount(5, $result);
        $this->assertTrue($result->hasPages());
    }

    public function test_list_filters_by_status(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::BEKLEMEDE->value,
        ]);

        $result = $this->listUseCase->execute(['status' => TalepDurumu::AKTIF->value]);

        $this->assertCount(1, $result);
        // talep_durumu is enum-cast on the model, compare raw DB value
        $this->assertEquals(TalepDurumu::AKTIF->value, $result->first()->getRawOriginal('talep_durumu'));
    }

    public function test_list_filters_by_il_id(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->create(['kisi_id' => $kisi->id, 'il_id' => 1]);
        Talep::factory()->create(['kisi_id' => $kisi->id, 'il_id' => 2]);

        $result = $this->listUseCase->execute(['il_id' => 1]);

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result->first()->il_id);
    }

    public function test_list_filters_by_search(): void
    {
        $kisi = Kisi::factory()->create(['ad' => 'Ahmet']);
        Talep::factory()->create(['kisi_id' => $kisi->id, 'baslik' => 'Deniz manzaralı daire']);
        Talep::factory()->create(['kisi_id' => $kisi->id, 'baslik' => 'Şehir içi arsa']);

        $result = $this->listUseCase->execute(['search' => 'Deniz']);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('Deniz', $result->first()->baslik);
    }

    public function test_summary_stats_returns_expected_keys(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::KARSIILANDI->value,
        ]);

        $stats = $this->listUseCase->getSummaryStats();

        $this->assertArrayHasKey('toplam', $stats);
        $this->assertArrayHasKey('aktif', $stats);
        $this->assertArrayHasKey('beklemede', $stats);
        $this->assertArrayHasKey('eslesen', $stats);
        $this->assertEquals(3, $stats['toplam']);
        $this->assertEquals(2, $stats['aktif']);
        $this->assertEquals(1, $stats['eslesen']);
    }

    public function test_form_data_returns_required_keys(): void
    {
        $formData = $this->listUseCase->getFormData();

        $this->assertArrayHasKey('iller', $formData);
        $this->assertArrayHasKey('kategoriler', $formData);
        $this->assertArrayHasKey('danismanlar', $formData);
        $this->assertArrayHasKey('ulkeler', $formData);
        $this->assertArrayHasKey('statuslar', $formData);
        $this->assertArrayHasKey('talepTipleri', $formData);
        $this->assertArrayHasKey('emlakTipleri', $formData);
    }

    public function test_form_data_emlak_tipleri_is_array(): void
    {
        // emlakTipleri delegates to getTalepTipleri() which mirrors TalepOrchestrator pattern
        $formData = $this->listUseCase->getFormData();

        $this->assertIsArray($formData['emlakTipleri']);
        $this->assertContains('Konut', $formData['emlakTipleri']);     // YayinTipi::SATILIK->label()
        $this->assertContains('Kiralık', $formData['emlakTipleri']);   // YayinTipi::KIRALIK->label()
        $this->assertContains('Arsa', $formData['emlakTipleri']);      // hardcoded merge
        $this->assertContains('İşyeri', $formData['emlakTipleri']);    // hardcoded merge
    }

    public function test_available_statuses_returns_collection(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ]);
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'talep_durumu' => TalepDurumu::IPTAL->value,
        ]);

        $statuses = $this->listUseCase->getAvailableStatuses();

        $this->assertCount(2, $statuses);
    }

    public function test_talep_list_criteria_from_array(): void
    {
        $criteria = TalepListCriteria::fromArray([
            'search'   => 'test',
            'status'   => TalepDurumu::AKTIF->value,
            'il_id'    => 5,
            'per_page' => 10,
        ]);

        $this->assertEquals('test', $criteria->search);
        $this->assertEquals(TalepDurumu::AKTIF->value, $criteria->status);
        $this->assertEquals(5, $criteria->ilId);
        $this->assertEquals(10, $criteria->perPage);
    }

    public function test_talep_list_criteria_is_empty(): void
    {
        $empty    = TalepListCriteria::fromArray([]);
        $notEmpty = TalepListCriteria::fromArray(['search' => 'x']);

        $this->assertTrue($empty->isEmpty());
        $this->assertFalse($notEmpty->isEmpty());
    }

    public function test_talep_list_criteria_to_array_filters_nulls(): void
    {
        $criteria = TalepListCriteria::fromArray([
            'search' => 'test',
            'il_id'  => null,
        ]);

        $array = $criteria->toArray();

        $this->assertArrayHasKey('search', $array);
        $this->assertArrayNotHasKey('il_id', $array);
    }

    // ─── Create Use Case Characterization ────────────────────────────────────

    public function test_create_talep_with_existing_kisi(): void
    {
        $kisi = Kisi::factory()->create();

        $command = TalepCreateCommand::fromRequest([
            'baslik'         => 'Beşiktaş ta 3+1',
            'kisi_id'        => $kisi->id,
            'aciklama'       => 'Deniz manzarası şart',
            'il_id'          => 34,
            'min_fiyat'      => 5000000,
            'max_fiyat'      => 8000000,
            'talep_durumu'   => TalepDurumu::AKTIF->value,
        ], $this->admin);

        $talep = $this->createUseCase->execute($command, $this->admin);

        $this->assertDatabaseHas('talepler', [
            'id'            => $talep->id,
            'baslik'        => 'Beşiktaş ta 3+1',
            'kisi_id'       => $kisi->id,
            'talep_durumu'  => TalepDurumu::AKTIF->value,
        ]);
    }

    public function test_create_talep_spillover_registers_kisi(): void
    {
        $talep = $this->createUseCase->executeFromSpillover([
            'baslik'       => 'Kadıköy 2+1 Talep',
            'kisi_ad'      => 'Ahmet',
            'kisi_soyad'   => 'Yılmaz',
            'kisi_telefon' => '05321112233',
            'kisi_email'   => 'ahmet.test@yalihan.test',
            'il_id'        => 34,
            'talep_durumu' => TalepDurumu::AKTIF->value,
        ], $this->admin);

        $this->assertDatabaseHas('talepler', [
            'id'     => $talep->id,
            'baslik' => 'Kadıköy 2+1 Talep',
        ]);

        $this->assertDatabaseHas('kisiler', [
            'ad'      => 'Ahmet',
            'soyad'   => 'Yılmaz',
            'telefon' => '05321112233',
        ]);
    }

    public function test_create_talep_sets_danisman_from_actor(): void
    {
        $kisi = Kisi::factory()->create();

        $command = TalepCreateCommand::fromRequest([
            'baslik'   => 'Test Talep',
            'kisi_id'  => $kisi->id,
            'aciklama' => 'Test',
        ], $this->admin);

        $talep = $this->createUseCase->execute($command, $this->admin);

        $this->assertEquals($this->admin->getAuthIdentifier(), $talep->danisman_id);
    }

    public function test_create_talep_respects_explicit_danisman(): void
    {
        $kisi     = Kisi::factory()->create();
        $danisman = User::factory()->create();

        $command = TalepCreateCommand::fromRequest([
            'baslik'      => 'Test Talep',
            'kisi_id'     => $kisi->id,
            'danisman_id' => $danisman->id,
            'aciklama'    => 'Test',
        ], $this->admin);

        $talep = $this->createUseCase->execute($command, $this->admin);

        $this->assertEquals($danisman->id, $talep->danisman_id);
    }

    public function test_create_command_from_array_maps_all_fields(): void
    {
        $data = [
            'baslik'           => 'Sahibinden 3+1',
            'kisi_id'          => 5,
            'il_id'            => 34,
            'alt_kategori_id'  => 2,
            'danisman_id'      => 3,
            'aciklama'         => 'Merkezi konum',
            'min_fiyat'        => '1000000',
            'max_fiyat'        => '2000000',
            'min_metrekare'    => '100',
            'max_metrekare'    => '150',
            'one_cikan'        => true,
            'ilce_id'          => 10,
            'mahalle_id'       => 20,
            'notlar'           => 'Acil satılık',
            'talep_durumu'     => TalepDurumu::AKTIF->value,
        ];

        $command = TalepCreateCommand::fromRequest($data);

        $this->assertEquals('Sahibinden 3+1', $command->baslik);
        $this->assertEquals(5, $command->kisiId);
        $this->assertEquals(34, $command->ilId);
        $this->assertEquals(2, $command->altKategoriId);
        $this->assertEquals(3, $command->danismanId);
        $this->assertEquals('Merkezi konum', $command->aciklama);
        $this->assertEquals(1000000.0, $command->minFiyat);
        $this->assertEquals(2000000.0, $command->maxFiyat);
        $this->assertEquals(100, $command->minMetrekare);
        $this->assertEquals(150, $command->maxMetrekare);
        $this->assertTrue($command->oneCikan);
        $this->assertEquals(10, $command->ilceId);
        $this->assertEquals(20, $command->mahalleId);
        $this->assertEquals('Acil satılık', $command->notlar);
        $this->assertEquals(TalepDurumu::AKTIF->value, $command->talepDurumu);
    }

    public function test_update_talep(): void
    {
        $kisi  = Kisi::factory()->create();
        $talep = Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'baslik'  => 'Eski başlık',
        ]);

        $command = TalepCreateCommand::fromRequest([
            'baslik'        => 'Güncellenmiş başlık',
            'kisi_id'      => $kisi->id,
            'aciklama'      => 'Yeni açıklama',
            'talep_durumu'  => TalepDurumu::BEKLEMEDE->value,
        ], $this->admin);

        $updated = $this->createUseCase->update($talep, $command, $this->admin);

        $this->assertEquals('Güncellenmiş başlık', $updated->baslik);
        // talep_durumu is enum-cast on the model, compare raw DB value
        $this->assertEquals(TalepDurumu::BEKLEMEDE->value, $updated->getRawOriginal('talep_durumu'));
        $this->assertEquals('Yeni açıklama', $updated->aciklama);
    }

    public function test_delete_talep(): void
    {
        $kisi  = Kisi::factory()->create();
        $talep = Talep::factory()->create(['kisi_id' => $kisi->id]);

        $result = $this->createUseCase->delete($talep, $this->admin);

        $this->assertTrue($result);
        $this->assertSoftDeleted('talepler', ['id' => $talep->id]);
    }

    public function test_create_without_kisi_throws(): void
    {
        $command = TalepCreateCommand::fromRequest([
            'baslik'   => 'Test',
            'kisi_id'  => null,
            'aciklama' => 'Test',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('kisi_id is required');

        $this->createUseCase->execute($command);
    }

    public function test_repository_interface_findorfail_throws_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->listUseCase->findOrFail(99999);
    }

    public function test_repository_interface_search_returns_collection(): void
    {
        $kisi = Kisi::factory()->create();
        Talep::factory()->create([
            'kisi_id' => $kisi->id,
            'baslik'  => 'Kiralık daire İzmir',
        ]);

        $results = $this->listUseCase->search('İzmir');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('İzmir', $results->first()->baslik);
    }
}
