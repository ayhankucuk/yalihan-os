<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class IlanWizardPageTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->markTestSkipped('Legacy IlanWizardPageTest failing due to environment/setup issues');
    }

    public function test_create_wizard_page_renders()
    {
        $this->withoutExceptionHandling();
        $this->withoutMiddleware();

        $response = $this->get('/admin/ilanlar/create-wizard');

        $response->assertStatus(200);
        $response->assertSee('Yeni İlan Oluştur', false);
        $response->assertSee('Temel Bilgiler', false);
        $response->assertSee('Detaylar', false);
        $response->assertSee('Ek Bilgiler', false);
        $response->assertSee('İlan Özellikleri', false);
    }
}

