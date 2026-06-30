<?php
/** @context7-ignore-file */

namespace Tests\Feature;

use App\Models\Ilan;
use App\Models\User;
use App\Exceptions\Context7ViolationException;
use Tests\TestCase;

class Context7GuardTest extends TestCase
{

    /** @test */
    public function it_blocks_forbidden_status_field_on_ilan_creation()
    {
        $this->expectException(Context7ViolationException::class);
        $this->expectExceptionMessage("CONTEXT7 VIOLATION: 'status' alanı kullanımı yasaktır"); // context7-ignore

        Ilan::create([
            'baslik' => 'Test Ilan',
            'status' => 'Aktif', // context7-ignore
            'yayin_durumu' => 'yayinda',
        ]);
    }

    /** @test */
    public function it_blocks_forbidden_active_field_on_user_update()
    {
        $user = User::factory()->create(['aktiflik_durumu' => true]);

        $this->expectException(Context7ViolationException::class);
        $this->expectExceptionMessage("CONTEXT7 VIOLATION: 'active' alanı kullanımı yasaktır"); // context7-ignore

        $user->update([
            'active' => false, // context7-ignore
        ]);
    }

    /** @test */
    public function it_allows_mühürlü_fields()
    {
        $ilan = Ilan::create([
            'baslik' => 'Test Ilan',
            'yayin_durumu' => 'yayinda',
        ]);

        $this->assertDatabaseHas('ilanlar', [
            'id' => $ilan->id,
            'yayin_durumu' => 'yayinda',
        ]);
    }
}
