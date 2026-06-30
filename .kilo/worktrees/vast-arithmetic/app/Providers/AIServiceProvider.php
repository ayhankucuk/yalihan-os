<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\AI\Contracts\AIProviderInterface::class, \App\Services\AI\Providers\DeepSeekProvider::class);

        $this->app->singleton('ai.client', function ($app) {
            // Basit AI client mock
            return new class
            {
                public function generate($prompt)
                {
                    return [
                        'success' => true,
                        'data' => [
                            'text' => 'AI yanıtı: '.substr($prompt, 0, 100).'...',
                            'confidence' => 0.85,
                        ],
                    ];
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
