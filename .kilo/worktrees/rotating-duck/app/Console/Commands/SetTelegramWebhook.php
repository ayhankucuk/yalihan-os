<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetTelegramWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the Telegram bot webhook URL using .env configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = config('services.telegram.bot_token');
        $webhookUrl = config('services.telegram.webhook_url');

        if (empty($token) || $token === 'ROTATED_BOT_TOKEN_REPLACE_ME') {
            $this->error('❌ Error: TELEGRAM_BOT_TOKEN is missing or not set in .env');
            return 1;
        }

        if (empty($webhookUrl)) {
            $this->error('❌ Error: TELEGRAM_WEBHOOK_URL is missing in .env');
            return 1;
        }

        $this->info("🚀 Setting Telegram Webhook to: {$webhookUrl}");

        try {
            $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
            ]);

            if ($response->successful() && $response->json('ok')) {
                $this->info('✅ Success: Telegram Webhook has been set successfully!');
                $this->line('Response: ' . $response->body());
                
                Log::info('Telegram Webhook set successfully', [
                    'url' => $webhookUrl,
                    'response' => $response->json()
                ]);
                
                return 0;
            }

            $this->error('❌ Failure: Telegram API returned an error.');
            $this->error('Response: ' . $response->body());
            
            Log::error('Telegram Webhook set failure', [
                'url' => $webhookUrl,
                'response' => $response->json()
            ]);
            
            return 1;

        } catch (\Exception $e) {
            $this->error('🚨 Critical Error: ' . $e->getMessage());
            Log::error('Telegram Webhook critical exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
