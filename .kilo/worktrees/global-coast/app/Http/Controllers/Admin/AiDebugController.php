<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Models\AiProviderDecision;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AiDebugController extends Controller
{
    /**
     * Display AI provider decisions for debugging
     */
    public function decisions(Request $request)
    {
        $query = AiProviderDecision::query()
            ->with(['kategori', 'yayinTipi'])
            ->orderBy('created_at', 'desc'); // context7-ignore

        // Filters
        if ($request->filled('provider')) {
            $query->where('chosen_provider', $request->provider);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('correlation_id')) {
            $query->where('correlation_id', 'like', '%' . $request->correlation_id . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $decisions = $query->paginate(50);

        $providers = AiProviderDecision::distinct()->pluck('chosen_provider');

        return view('admin.ai.debug.decisions', compact('decisions', 'providers'));
    }
}
