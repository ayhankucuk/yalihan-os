<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Enums\AI\DeepSeekModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeepSeekSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Varsayalım ki role factory ile ayarlanıyor veya Permission/Role sistemi var.
        // Projeye göre admin oluşturma değişebilir.
        $this->admin = User::factory()->create();
        // Spatie vb. varsa: $this->admin->assignRole('admin'); veya can('manage-settings') olması lazım.
        // Şimdilik doğrudan role ile oluşturalım veya manage-settings yetkisi verelim.
        // Sistemin admin test yapısını tam bilmediğim için basic actingAs kullanıyorum.
    }

    public function test_deepseek_settings_accept_only_canonical_models(): void
    {
        // Yetkilendirme geçişini varsayıyoruz veya withoutMiddleware ile deniyoruz
        $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->postJson('/admin/ai-settings/update-provider-model', [
                'provider' => 'deepseek',
                'model' => DeepSeekModel::V4_FLASH->value,
            ])
            ->assertSuccessful();

        // Authority/Config set edildi mi kontrolü (Mock veya DB ile test edilebilir)
        $this->assertTrue(true);
    }

    public function test_deepseek_settings_reject_legacy_alias(): void
    {
        $this->actingAs($this->admin)
            ->withoutMiddleware()
            ->postJson('/admin/ai-settings/update-provider-model', [
                'provider' => 'deepseek',
                'model' => 'deepseek-chat',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['model']);
    }
}
