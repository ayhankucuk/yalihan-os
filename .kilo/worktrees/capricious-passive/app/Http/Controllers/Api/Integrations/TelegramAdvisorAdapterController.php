<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\ConversationalAdvisorService;
use Illuminate\Support\Facades\Log;

class TelegramAdvisorAdapterController extends Controller
{
    public function __construct(
        private ConversationalAdvisorService $advisorService
    ) {}

    public function handleWebhook(Request $request)
    {
        // Example basic payload structure check
        if (!$request->has('message.text')) {
            return response()->json(['is_success' => true]); // Ignore non-text messages
        }

        $chatId = $request->input('message.chat.id');
        $query = $request->input('message.text');

        try {
            $result = $this->advisorService->processQuery($query);

            $responseText = $result['advisor_response'] ?? 'Analiz edilemedi.';

            // In reality, you'd send this back to Telegram API
            Log::info("Telegram AI Answered: {$responseText} to {$chatId}");

            return response()->json(['is_success' => true]);

        } catch (\Exception $e) {
            Log::error('TelegramAdvisorAdapter Error: ' . $e->getMessage());
            return response()->json(['is_success' => false], 500);
        }
    }
}
