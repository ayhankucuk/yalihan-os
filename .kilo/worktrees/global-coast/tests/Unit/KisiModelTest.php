<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Kisi;

/**
 * Kisi Model Unit Tests
 * 
 * Context7: Unit tests for Kisi model methods and scopes
 */
class KisiModelTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        
        // Detach Kisi observer to prevent auto-task creation
        // This is the most reliable way to disable observers in tests
        $events = Kisi::getEventDispatcher();
        if ($events) {
            $events->forget('eloquent.created: App\Models\Kisi');
            $events->forget('eloquent.updated: App\Models\Kisi');
        }
    }

    /**
     * Test: scopeAktif returns only active contacts
     */
    public function test_scope_aktif_returns_only_active_contacts(): void
    {
        // Arrange
        $aktif1 = Kisi::factory()->create(['aktiflik_durumu' => true]);
        $aktif2 = Kisi::factory()->create(['aktiflik_durumu' => true]);
        $pasif = Kisi::factory()->create(['aktiflik_durumu' => false]);
        
        // Act
        $result = Kisi::aktif()->get();
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($aktif1));
        $this->assertTrue($result->contains($aktif2));
        $this->assertFalse($result->contains($pasif));
    }

    /**
     * Test: scopePasif returns only inactive contacts
     */
    public function test_scope_pasif_returns_only_inactive_contacts(): void
    {
        // Arrange
        $aktif = Kisi::factory()->create(['aktiflik_durumu' => true]);
        $pasif1 = Kisi::factory()->create(['aktiflik_durumu' => false]);
        $pasif2 = Kisi::factory()->create(['aktiflik_durumu' => false]);
        
        // Act
        $result = Kisi::pasif()->get();
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($pasif1));
        $this->assertTrue($result->contains($pasif2));
        $this->assertFalse($result->contains($aktif));
    }

    /**
     * Test: scopeDurumAktif is alias for scopeAktif
     */
    public function test_scope_durum_aktif_works(): void
    {
        // Arrange
        Kisi::factory()->count(3)->create(['aktiflik_durumu' => true]);
        Kisi::factory()->count(2)->create(['aktiflik_durumu' => false]);
        
        // Act
        $result = Kisi::durumAktif()->get();
        
        // Assert
        $this->assertCount(3, $result);
    }

    /**
     * Test: scopeActive is alias for scopeAktif
     */
    public function test_scope_active_works(): void
    {
        // Arrange
        Kisi::factory()->count(4)->create(['aktiflik_durumu' => true]);
        Kisi::factory()->count(1)->create(['aktiflik_durumu' => false]);
        
        // Act
        $result = Kisi::active()->get();
        
        // Assert
        $this->assertCount(4, $result);
    }

    /**
     * Test: tam_ad accessor concatenates first and last name
     */
    public function test_tam_ad_accessor_concatenates_names(): void
    {
        // Arrange & Act
        $kisi = Kisi::factory()->create([
            'ad' => 'Mehmet',
            'soyad' => 'Demir',
        ]);
        
        // Assert
        $this->assertEquals('Mehmet Demir', $kisi->tam_ad);
    }

    /**
     * Test: tam_ad accessor handles single name
     */
    public function test_tam_ad_accessor_handles_single_name(): void
    {
        // Arrange & Act
        $kisi = Kisi::factory()->create([
            'ad' => 'Ahmet',
            'soyad' => '', // Empty string instead of null
        ]);
        
        // Assert
        $this->assertStringContainsString('Ahmet', $kisi->tam_ad);
    }

    /**
     * Test: Kisi can be created with minimum required fields
     */
    public function test_kisi_can_be_created_with_minimum_fields(): void
    {
        // Arrange & Act
        $kisi = Kisi::create([
            'ad' => 'Test',
            'soyad' => 'User',
            'telefon' => '5551234567',
        ]);
        
        // Assert
        $this->assertInstanceOf(Kisi::class, $kisi);
        $this->assertDatabaseHas('kisiler', [
            'ad' => 'Test',
            'soyad' => 'User',
            'telefon' => '5551234567',
        ]);
    }

    /**
     * Test: Kisi soft deletes work
     */
    public function test_kisi_soft_deletes_work(): void
    {
        // Arrange
        $kisi = Kisi::factory()->create();
        $kisiId = $kisi->id;
        
        // Act
        $kisi->delete();
        
        // Assert
        $this->assertSoftDeleted('kisiler', ['id' => $kisiId]);
        $this->assertNull(Kisi::find($kisiId));
        $this->assertNotNull(Kisi::withTrashed()->find($kisiId));
    }
}
