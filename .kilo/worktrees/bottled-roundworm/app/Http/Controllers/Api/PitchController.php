<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\IlanNot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PitchController extends Controller
{
    /**
     * Get pitch for WhatsApp sharing
     * 
     * @param int $noteId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function shareToWhatsapp($noteId)
    {
        try {
            $note = IlanNot::where('not_tipi', 'pitch')->findOrFail($noteId);
            
            // Format text for WhatsApp URL
            $text = urlencode($note->not_icerigi);
            
            // If the request expects JSON (e.g., from an app or frontend calling this DB status)
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'text' => $note->not_icerigi,
                    'whatsapp_url' => "https://wa.me/?text={$text}"
                ]);
            }
            
            // Otherwise redirect to WhatsApp Web/App
            return redirect()->away("https://wa.me/?text={$text}");
            
        } catch (\Exception $e) {
            Log::error("Pitch WhatsApp Share Error: " . $e->getMessage());
            
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Pitch bulunamadı.'], 404);
            }
            
            abort(404, 'Pitch bulunamadı.');
        }
    }
}
