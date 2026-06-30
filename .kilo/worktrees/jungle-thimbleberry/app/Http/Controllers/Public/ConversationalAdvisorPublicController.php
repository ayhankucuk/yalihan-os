<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AI\ConversationalAdvisorService;

class ConversationalAdvisorPublicController extends Controller
{
    public function __construct(
        private ConversationalAdvisorService $advisorService
    ) {}

    public function index()
    {
        return view('public.ai-advisor');
    }

    public function query(Request $request)
    {
        $validated = $request->validate([
            'query'      => 'required|string|max:1000',
            'listing_id' => 'nullable|integer|min:1',
        ]);

        // Konuşma geçmişini session'dan al (max 5 tur)
        $history = session('ai_advisor_history', []);

        // Bağlam: listing_id request veya son history'den
        $context = [];
        if (!empty($validated['listing_id'])) {
            $context['listing_id'] = (int) $validated['listing_id'];
        }

        // Rate limiting applied via middleware on routes
        $result = $this->advisorService->processQuery(
            $validated['query'],
            $context,
            $history
        );

        // History güncelle — listing_id + entities de sakla (B: entity carryover için)
        $turn = [
            'q'        => $validated['query'],
            'a'        => $result['advisor_response'],
            'intent'   => $result['intent_detected'],
            'entities' => $result['entities_parsed'] ?? [],
        ];
        if (!empty($context['listing_id'])) {
            $turn['listing_id'] = $context['listing_id'];
        }

        $history[] = $turn;
        if (count($history) > 5) {
            array_shift($history);
        }
        session(['ai_advisor_history' => $history]);

        return response()->json($result);
    }

    /**
     * Konuşma geçmişini temizle
     */
    public function clearHistory()
    {
        session()->forget('ai_advisor_history');
        return response()->json(['ok' => true]);
    }
}
