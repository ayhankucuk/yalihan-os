<?php

namespace Tests\Unit\Services;

use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ResponseServiceTest extends TestCase
{
    /**
     * Test success response — Phase 36 contract: { success, data, meta, error } + backward-compatible message
     */
    public function test_success_response(): void
    {
        $response = ResponseService::success(['id' => 1], 'İşlem başarılı');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals('İşlem başarılı', $data['message']);
        $this->assertEquals(['id' => 1], $data['data']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('timestamp', $data['meta']);
        $this->assertNull($data['error']);
    }

    /**
     * Test error response — Phase 36 contract
     */
    public function test_error_response(): void
    {
        $response = ResponseService::error('Bir hata oluştu', 400, [], 'TEST_ERROR');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(400, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Bir hata oluştu', $data['message']);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('TEST_ERROR', $data['error']['code']);
        $this->assertEquals('Bir hata oluştu', $data['error']['message']);
    }

    /**
     * Test validation error response
     */
    public function test_validation_error_response(): void
    {
        $errors = [
            'email' => ['Email geçersiz'],
            'password' => ['Şifre gerekli'],
        ];

        $response = ResponseService::validationError($errors);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Validasyon hatası', $data['message']);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('VALIDATION_ERROR', $data['error']['code']);
        $this->assertEquals($errors, $data['error']['details']);
    }

    /**
     * Test not found response
     */
    public function test_not_found_response(): void
    {
        $response = ResponseService::notFound('Kayıt bulunamadı');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Kayıt bulunamadı', $data['message']);
        $this->assertEquals('NOT_FOUND', $data['error']['code']);
    }

    /**
     * Test unauthorized response
     */
    public function test_unauthorized_response(): void
    {
        $response = ResponseService::unauthorized('Yetkisiz erişim');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Yetkisiz erişim', $data['message']);
        $this->assertEquals('UNAUTHORIZED', $data['error']['code']);
    }

    /**
     * Test forbidden response
     */
    public function test_forbidden_response(): void
    {
        $response = ResponseService::forbidden('Bu işlem için yetkiniz yok');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Bu işlem için yetkiniz yok', $data['message']);
        $this->assertEquals('FORBIDDEN', $data['error']['code']);
    }

    /**
     * Test server error response
     */
    public function test_server_error_response(): void
    {
        $response = ResponseService::serverError('Sunucu hatası');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Sunucu hatası', $data['message']);
        $this->assertEquals('SERVER_ERROR', $data['error']['code']);
    }
}
