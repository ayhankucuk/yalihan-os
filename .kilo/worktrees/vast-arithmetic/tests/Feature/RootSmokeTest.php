<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RootSmokeTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
    }
}
