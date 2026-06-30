<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\ChatService;
use App\Services\AI\PriceService;
use App\Services\AI\SuggestService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

class AdminAIController extends Controller
{
    public function __construct(
        private ChatService $chat,
        private PriceService $price,
        private SuggestService $suggest
    ) {}

    public function chat(Request $request)
    {
        $payload = $request->all();
        $res = $this->chat->chat($payload);

        return ResponseService::success($res['data'], $res['message']);
    }

    public function pricePredict(Request $request)
    {
        $payload = $request->all();
        $res = $this->price->predict($payload);

        return ResponseService::success($res['data'], $res['message']);
    }

    public function suggestFeatures(Request $request)
    {
        $context = $request->all();
        $res = $this->suggest->suggestFeatures($context);

        return ResponseService::success($res['data'], $res['message']);
    }

    public function analytics()
    {
        $stats = [
            'total_requests' => \App\Models\AiLog::count(),
            'successful_requests' => \App\Models\AiLog::where('calisma_durumu', 'success')->count(),
            'failed_requests' => \App\Models\AiLog::where('calisma_durumu', 'error')->count(),
            'average_response_time' => \App\Models\AiLog::avg('duration_ms'),
            'cancelled_requests' => \App\Models\AiLog::where('calisma_durumu', 'cancelled')->count(),
        ];

        return ResponseService::success($stats, 'AI analytics');
    }

    public function promptPresets()
    {
        $lib = app(\App\Services\AI\PromptLibrary::class);
        $presets = $lib->list();
        $data = [];
        foreach ($presets as $key => $conf) {
            $data[] = ['key' => $key, 'title' => $conf['title'] ?? $key, 'version' => $conf['version'] ?? null];
        }

        return ResponseService::success(['presets' => $data], 'Prompt presets');
    }

    public function chatStream(Request $request)
    {
        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'prompt_preset' => 'nullable|string|max:50',
        ]);
        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($validated) {
            $payload = [
                'prompt' => $validated['prompt'],
                'prompt_preset' => $validated['prompt_preset'] ?? null,
            ];
            $res = $this->chat->chat($payload);
            $text = (string) ($res['data']['text'] ?? '');
            $parts = preg_split('/\s+/', $text) ?: [];
            foreach ($parts as $p) {
                echo 'data: '.trim($p)."\n\n";
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                flush();
                usleep(25000);
            }
            echo "event: done\n";
            echo "data: end\n\n";
            flush();
        }, 200, $headers);
    }
}
