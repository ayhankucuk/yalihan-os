<?php

namespace Tests\Unit\Traits;

use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidatesApiRequestsTest extends TestCase
{
    use ValidatesApiRequests;

    /**
     * Test validateRequest method
     */
    public function test_validate_request_success(): void
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $validated = $this->validateRequest($request, $rules);

        $this->assertEquals('test@example.com', $validated['email']);
        $this->assertEquals('password123', $validated['password']);
    }

    /**
     * Test validateRequest method with validation failure
     */
    public function test_validate_request_failure(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/test', 'POST', [
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $this->validateRequest($request, $rules);
    }

    /**
     * Test validateRequestWithResponse method
     */
    public function test_validate_request_with_response_success(): void
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $result = $this->validateRequestWithResponse($request, $rules);

        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('password123', $result['password']);
    }

    /**
     * Test validateRequestWithResponse method with validation failure
     */
    public function test_validate_request_with_response_failure(): void
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $result = $this->validateRequestWithResponse($request, $rules);

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $result);
        $this->assertEquals(422, $result->getStatusCode());
    }

    /**
     * Test validateRequestFlexible method
     */
    public function test_validate_request_flexible(): void
    {
        $request = Request::create('/test', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8',
        ];

        $result = $this->validateRequestFlexible($request, $rules);

        $this->assertIsArray($result);
        $this->assertEquals('test@example.com', $result['email']);
    }
}
