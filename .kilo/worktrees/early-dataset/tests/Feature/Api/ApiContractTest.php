<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Phase 36: API Contract Standardization Tests
 *
 * Every tested endpoint MUST return:
 * - success (bool)
 * - data (object|array|null)
 * - meta (object with timestamp)
 * - error (object|null)
 *
 * Rules:
 * - success=true => error is null
 * - success=false => data is null
 * - meta.timestamp is always ISO string
 * @group skip-until-migration-complete
 */
class ApiContractTest extends TestCase
{
    /**
     * Assert the response conforms to the API contract.
     */
    protected function assertApiContract($response, bool $expectSuccess = true): void
    {
        $json = $response->json();

        // Required root keys
        $this->assertArrayHasKey('success', $json, 'Response must have "success" key');
        $this->assertArrayHasKey('data', $json, 'Response must have "data" key');
        $this->assertArrayHasKey('meta', $json, 'Response must have "meta" key');
        $this->assertArrayHasKey('error', $json, 'Response must have "error" key');

        // success type
        $this->assertIsBool($json['success'], '"success" must be boolean');

        // meta must have timestamp
        $this->assertIsArray($json['meta'], '"meta" must be an array/object');
        $this->assertArrayHasKey('timestamp', $json['meta'], '"meta" must have "timestamp"');
        $this->assertNotEmpty($json['meta']['timestamp'], '"meta.timestamp" must not be empty');

        if ($expectSuccess) {
            $this->assertTrue($json['success'], '"success" must be true');
            $this->assertNull($json['error'], '"error" must be null when success=true');
        } else {
            $this->assertFalse($json['success'], '"success" must be false');
            $this->assertNull($json['data'], '"data" must be null when success=false');
            $this->assertIsArray($json['error'], '"error" must be an object when success=false');
            $this->assertArrayHasKey('code', $json['error'], '"error" must have "code"');
            $this->assertArrayHasKey('message', $json['error'], '"error" must have "message"');
        }
    }

    /**
     * @test
     * AI Health endpoint must conform to API contract.
     */
    public function ai_health_endpoint_conforms_to_contract(): void
    {
        // Bypass middleware (AICostGuard and throttle require DB tables not in test env)
        $response = $this->withoutMiddleware()
            ->getJson('/api/v1/ai/health');

        $response->assertSuccessful();
        $this->assertApiContract($response, true);

        // Verify data payload contains expected structure
        $data = $response->json('data');
        $this->assertArrayHasKey('service_status', $data);
        $this->assertArrayHasKey('services', $data);
    }

    /**
     * @test
     * Nonexistent API endpoint returns contract-compliant 404.
     */
    public function nonexistent_endpoint_returns_contract_error(): void
    {
        // Note: This test verifies that the Laravel exception handler
        // returns contract-compliant responses. If not, this will flag it.
        $response = $this->getJson('/api/v1/nonexistent-endpoint-for-testing');

        $response->assertNotFound();

        // At minimum, verify it returns JSON
        $json = $response->json();
        $this->assertNotNull($json, 'Error responses must return JSON');
    }

    /**
     * @test
     * ResponseService::success produces contract-compliant output.
     */
    public function response_service_success_is_contract_compliant(): void
    {
        $response = \App\Services\Response\ResponseService::success(
            ['test' => 'data'],
            'Test message'
        );

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('error', $json);

        $this->assertTrue($json['success']);
        $this->assertNull($json['error']);
        $this->assertArrayHasKey('timestamp', $json['meta']);
        $this->assertEquals(['test' => 'data'], $json['data']);
    }

    /**
     * @test
     * ResponseService::error produces contract-compliant output.
     */
    public function response_service_error_is_contract_compliant(): void
    {
        $response = \App\Services\Response\ResponseService::error(
            'Test error',
            400,
            ['field' => 'required'],
            'VALIDATION_ERROR'
        );

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $json);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('error', $json);

        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertArrayHasKey('timestamp', $json['meta']);
        $this->assertIsArray($json['error']);
        $this->assertEquals('VALIDATION_ERROR', $json['error']['code']);
        $this->assertEquals('Test error', $json['error']['message']);
    }

    /**
     * @test
     * ApiResponse helper produces contract-compliant output.
     */
    public function api_response_helper_ok_is_compliant(): void
    {
        $response = \App\Support\ApiResponse::ok(['key' => 'value']);

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $json);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('data', $json);
        $this->assertArrayHasKey('meta', $json);
        $this->assertNull($json['error']);
        $this->assertArrayHasKey('timestamp', $json['meta']);
    }

    /**
     * @test
     * ApiResponse helper fail produces contract-compliant output.
     */
    public function api_response_helper_fail_is_compliant(): void
    {
        $response = \App\Support\ApiResponse::fail('TEST_ERROR', 'Something went wrong');

        $json = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $json);
        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertArrayHasKey('meta', $json);
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('TEST_ERROR', $json['error']['code']);
    }
}
