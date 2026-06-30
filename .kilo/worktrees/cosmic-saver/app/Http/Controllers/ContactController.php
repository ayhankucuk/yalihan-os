<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function property(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Log::channel('security')->info('property-contact', [
            'ilan_id' => $id,
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'user_id' => auth()->id() ?? null,
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Mesajınız iletildi.');
    }
}
