<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AIFrontend\AIQueryInterpreter;
use App\Services\AIFrontend\AIListingSearchService;
use App\Services\AIFrontend\AIPropertyAdvisorService;
use App\Services\AIFrontend\AIResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIFrontendAssistantController extends Controller
{
    protected AIQueryInterpreter $interpreter;
    protected AIListingSearchService $searchService;
    protected AIPropertyAdvisorService $advisorService;
    protected AIResponseFormatter $formatter;

    public function __construct(
        AIQueryInterpreter $interpreter,
        AIListingSearchService $searchService,
        AIPropertyAdvisorService $advisorService,
        AIResponseFormatter $formatter
    ) {
        $this->interpreter = $interpreter;
        $this->searchService = $searchService;
        $this->advisorService = $advisorService;
        $this->formatter = $formatter;
    }

    /**
     * Handle AI Assistant query.
     * POST /ai/assistant/query
     */
    public function query(Request $request)
    {
        $startTime = microtime(true);
        $message = $request->input('message');

        if (empty($message)) {
            return response()->json(['error' => 'Mesaj boş olamaz.'], 400);
        }

        try {
            // 1. Interpret Intent
            $intent = $this->interpreter->interpret($message);

            // 2. Search listings (CQRS Projection)
            $results = $this->searchService->search($intent);

            // 3. Analyze results (Cortex)
            $analysis = $this->advisorService->analyzeResults($results, $intent);

            // 4. Format response
            $response = $this->formatter->format([
                'intent' => $intent,
                'results' => $results,
                'analysis' => $analysis
            ], $message);

            $executionTime = microtime(true) - $startTime;

            // 5. Log for telemetry (ai_query_log)
            $this->logQuery($message, $intent, $executionTime, $results->count());

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error("AI Assistant Query failed: " . $e->getMessage(), [
                'query' => $message,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'İşlem sırasında bir hata oluştu.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function logQuery(string $query, array $intent, float $time, int $count)
    {
        try {
            // Telemetri logu — Eloquent model üzerinden (DB::table controller yasağı)
            \App\Models\AiQueryLog::create([
                'query'          => $query,
                'intent'         => json_encode($intent),
                'execution_time' => $time,
                'result_count'   => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log AI query: " . $e->getMessage());
        }
    }
}
