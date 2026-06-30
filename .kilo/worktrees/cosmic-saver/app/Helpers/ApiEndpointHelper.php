<?php

/**
 * API Endpoint Helper
 *
 * Context7 Standard: C7-API-ENDPOINT-HELPER-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * API endpoint'leri için helper fonksiyonlar.
 * Config cache sorununu önlemek için closure'lar buraya taşındı.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

class ApiEndpointHelper
{
    /**
     * Replace parameters in endpoint URL
     *
     * @param string $endpoint Endpoint URL with placeholders (e.g., '/api/users/{id}')
     * @param array $params Parameters to replace (e.g., ['id' => 123])
     * @return string Endpoint URL with replaced parameters
     */
    public static function replaceParams(string $endpoint, array $params = []): string
    {
        $url = $endpoint;
        
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
            $url = str_replace('{' . $key . '?}', $value, $url);
        }
        
        // Remove optional parameters that weren't replaced
        $url = preg_replace('/\{[^}]+\?\}/', '', $url);
        
        return $url;
    }

    /**
     * Get full URL for an endpoint
     *
     * @param string $endpoint Endpoint path
     * @param array $params Parameters to replace
     * @return string Full URL
     */
    public static function getUrl(string $endpoint, array $params = []): string
    {
        $baseUrl = config('api-endpoints.base_url', config('app.url'));
        $endpoint = self::replaceParams($endpoint, $params);
        
        return rtrim($baseUrl, '/') . $endpoint;
    }

    /**
     * Get endpoint from config
     *
     * @param string $path Dot notation path (e.g., 'location.districts')
     * @return string|null Endpoint path
     */
    public static function get(string $path): ?string
    {
        return config("api-endpoints.{$path}");
    }

    /**
     * Get endpoint with replaced parameters
     *
     * @param string $path Dot notation path
     * @param array $params Parameters to replace
     * @return string|null Endpoint URL with replaced parameters
     */
    public static function getWithParams(string $path, array $params = []): ?string
    {
        $endpoint = self::get($path);
        
        if (!$endpoint) {
            return null;
        }
        
        return self::replaceParams($endpoint, $params);
    }
}

