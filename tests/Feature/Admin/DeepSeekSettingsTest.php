<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Enums\AI\DeepSeekModel;
use Tests\TestCase;

class DeepSeekSettingsTest extends TestCase
{

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->createAdminUser();
    }

    public function test_deepseek_settings_accept_only_canonical_models(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/ai-settings/update-provider-model', [
                'provider' => 'deepseek',
                'model' => DeepSeekModel::V4_FLASH->value,
            ])
            ->assertSuccessful();

        $this->assertTrue(true);
    }

    public function test_deepseek_settings_reject_legacy_alias(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/ai-settings/update-provider-model', [
                'provider' => 'deepseek',
                'model' => 'invalid-model',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['model']);
    }
}
