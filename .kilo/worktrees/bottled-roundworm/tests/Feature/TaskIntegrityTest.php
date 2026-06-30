<?php

namespace Tests\Feature;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Manually truncate to bypass SAVEPOINT issues and data pollution
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\DB::table('gorevler')->truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
    }

    /**
     * Override global DatabaseTransactions from TestCase to bypass SAVEPOINT errors
     */
    protected function beginDatabaseTransaction()
    {
        // Do nothing - bypass infrastructure transaction wrapping
    }

    /** @test */
    public function it_can_create_a_task_using_the_modular_model_with_synced_fields()
    {
        $user = User::factory()->create();

        $task = Gorev::create([
            'baslik' => 'Test Görevi',
            'aciklama' => 'Test Açıklama',
            'atanan_user_id' => $user->id,
            'gorev_durumu' => 'beklemede',
            'oncelik' => 'normal',
        ]);

        $this->assertDatabaseHas('gorevler', [
            'id' => $task->id,
            'baslik' => 'Test Görevi',
            'atanan_user_id' => $user->id,
            'gorev_durumu' => 'beklemede',
        ]);
    }

    /** @test */
    public function it_supports_the_canonical_durum_bridge()
    {
        $task = new Gorev();
        
        // Test Mutator
        $task->durum = 'devam_ediyor';
        $this->assertEquals('devam_ediyor', $task->gorev_durumu);
        
        // Test Accessor
        $task->gorev_durumu = 'tamamlandi';
        $this->assertEquals('tamamlandi', $task->durum);
    }

    /** @test */
    public function scope_aktif_includes_beklemede_status()
    {
        Gorev::create([
            'baslik' => 'Beklemede Görev',
            'gorev_durumu' => 'beklemede',
        ]);

        Gorev::create([
            'baslik' => 'Devam Eden Görev',
            'gorev_durumu' => 'devam_ediyor',
        ]);

        Gorev::create([
            'baslik' => 'Tamamlanan Görev',
            'gorev_durumu' => 'tamamlandi',
        ]);

        $aktifGorevler = Gorev::aktif()->get();

        $this->assertCount(2, $aktifGorevler);
        $this->assertTrue($aktifGorevler->contains('baslik', 'Beklemede Görev'));
        $this->assertTrue($aktifGorevler->contains('baslik', 'Devam Eden Görev'));
        $this->assertFalse($aktifGorevler->contains('baslik', 'Tamamlanan Görev'));
    }

    /** @test */
    public function it_supports_tamamla_and_iptalet_bridge_methods()
    {
        $task = Gorev::create(['baslik' => 'Bridge Test', 'gorev_durumu' => 'bekliyor']);
        
        $task->tamamla();
        $this->assertEquals('tamamlandi', $task->fresh()->gorev_durumu);
        $this->assertNotNull($task->fresh()->bitis_tarihi);

        $task2 = Gorev::create(['baslik' => 'Bridge Test 2', 'gorev_durumu' => 'bekliyor']);
        $task2->iptalEt();
        $this->assertEquals('iptal', $task2->fresh()->gorev_durumu);
    }
}
