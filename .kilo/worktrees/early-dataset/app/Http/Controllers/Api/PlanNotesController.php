<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PlanNotesController extends Controller
{
    public function query(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'required|string|min:3',
            'doc_paths' => 'array',
            'doc_paths.*' => 'string',
        ]);

        $notes = $validated['notes'];
        $paths = $validated['doc_paths'] ?? [];
        $docs = [];
        foreach ($paths as $p) {
            try {
                $full = base_path(trim($p));
                if (is_file($full)) {
                    $content = file_get_contents($full);
                    $docs[] = [
                        'path' => $p,
                        'content' => Str::limit($content ?? '', 15000, ''),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('PlanNotes doc read failed', ['path' => $p, 'error' => $e->getMessage()]);
            }
        }

        $prompt = "Plan Notları Sorgu:\n".$notes."\n\nDoküman Özeti:\n".collect($docs)->map(function ($d) {
            return '['.$d['path']."]\n".$d['content'];
        })->implode("\n\n");

        return response()->json([
            'success' => true,
            'prepared_prompt' => $prompt,
            'docs' => $docs,
        ], Response::HTTP_OK);
    }
}
