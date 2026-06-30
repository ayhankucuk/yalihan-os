<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChangelogController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:manage-settings');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|string',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable',
            'field' => 'nullable|string',
            'old_value' => 'nullable',
            'new_value' => 'nullable',
            'source' => 'nullable|string',
            'context' => 'nullable|array',
        ]);

        $payload = [
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'action' => $data['action'],
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'field' => $data['field'] ?? null,
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'source' => $data['source'] ?? 'manual',
            'context' => $data['context'] ?? [],
        ];

        Log::channel('module_changes')->info('AdminChangelog', $payload);

        return response()->json([
            'success' => true,
            'message' => 'Changelog kaydedildi',
            'data' => $payload,
        ]);
    }
}
