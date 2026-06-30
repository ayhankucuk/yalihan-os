<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\AIMessage;
use App\Models\Communication;
use App\Services\AI\AIMessageService;
use App\Actions\Admin\AI\RejectAIMessageAction;
use App\Actions\Admin\AI\UpdateAIMessageContentAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AI Mesaj Controller
 *
 * Context7 Standardı: C7-AI-MESSAGE-CONTROLLER-2025-11-20
 *
 * Admin panel'de AI ile oluşturulan mesaj taslaklarını yönetir.
 * Onay, reddetme ve gönderme işlemlerini gerçekleştirir.
 */
class AIMessageController extends Controller
{
    protected AIMessageService $messageService;

    public function __construct(AIMessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * Mesaj taslaklarını listele
     */
    public function index(Request $request)
    {
        $query = AIMessage::with(['conversation', 'communication', 'approver']);

        // Filtreleme
        if ($request->has('mesaj_durumu')) {
            $query->where('yayin_durumu', $request->mesaj_durumu);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        // Arama
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('communication', function ($q) use ($search) {
                        $q->where('message', 'like', "%{$search}%")
                            ->orWhere('sender_name', 'like', "%{$search}%");
                    });
            });
        }

        $messages = $query->orderBy('created_at', 'desc')->paginate(20); // context7-ignore

        return view('admin.ai.mesaj-taslaklari.index', compact('messages'));
    }

    /**
     * Mesaj taslağı detayını göster
     */
    public function show($id)
    {
        $message = AIMessage::with(['conversation', 'communication', 'approver'])->findOrFail($id);

        return view('admin.ai.mesaj-taslaklari.show', compact('message'));
    }

    /**
     * Yeni mesaj taslağı oluştur
     */
    public function create(Request $request)
    {
        $communicationId = $request->get('communication_id');
        $communication = $communicationId ? Communication::find($communicationId) : null;

        return view('admin.ai.mesaj-taslaklari.create', compact('communication'));
    }

    /**
     * Mesaj taslağı oluştur
     */
    public function store(Request $request)
    {
        $request->validate([
            'communication_id' => 'required|exists:communications,id',
        ]);

        try {
            $message = $this->messageService->generateDraftReply($request->communication_id);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesaj taslağı oluşturuldu',
                    'data' => $message,
                ]);
            }

            return redirect()->route('admin.ai.mesaj-taslaklari.show', $message->id)
                ->with('success', 'Mesaj taslağı oluşturuldu');

        } catch (\Exception $e) {
            Log::error('Mesaj taslağı oluşturma hatası', [
                'error' => $e->getMessage(),
                'communication_id' => $request->communication_id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Taslak oluşturulurken hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Taslak oluşturulurken hata oluştu');
        }
    }

    /**
     * Mesaj taslağını onayla
     */
    public function approve(Request $request, $id)
    {
        try {
            $message = $this->messageService->approveMessage($id, auth()->id());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesaj taslağı onaylandı',
                    'data' => $message,
                ]);
            }

            return redirect()->back()->with('success', 'Mesaj taslağı onaylandı');

        } catch (\Exception $e) {
            Log::error('Mesaj taslağı onaylama hatası', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Onaylama sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Onaylama sırasında hata oluştu');
        }
    }

    /**
     * Mesaj taslağını reddet
     */
    public function reject(Request $request, $id)
    {
        try {
            $message = AIMessage::findOrFail($id);

            $request->validate([
                'rejection_reason' => 'nullable|string|max:500',
            ]);

            app(RejectAIMessageAction::class)->handle($message);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesaj taslağı reddedildi',
                    'data' => $message,
                ]);
            }

            return redirect()->back()->with('success', 'Mesaj taslağı reddedildi');

        } catch (\Exception $e) {
            Log::error('Mesaj taslağı reddetme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reddetme sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Reddetme sırasında hata oluştu');
        }
    }

    /**
     * Mesaj taslağını gönder
     */
    public function send(Request $request, $id)
    {
        try {
            $message = $this->messageService->sendMessage($id);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesaj başarıyla gönderildi',
                    'data' => $message,
                ]);
            }

            return redirect()->back()->with('success', 'Mesaj başarıyla gönderildi');

        } catch (\Exception $e) {
            Log::error('Mesaj gönderme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gönderme sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Gönderme sırasında hata oluştu');
        }
    }

    /**
     * Mesaj taslağını düzenle
     */
    public function edit($id)
    {
        $message = AIMessage::with(['conversation', 'communication'])->findOrFail($id);

        return view('admin.ai.mesaj-taslaklari.edit', compact('message'));
    }

    /**
     * Mesaj taslağını güncelle
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        try {
            $message = AIMessage::findOrFail($id);
            app(UpdateAIMessageContentAction::class)->handle($message, $request->content);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mesaj taslağı güncellendi',
                    'data' => $message,
                ]);
            }

            return redirect()->route('admin.ai.mesaj-taslaklari.show', $message->id)
                ->with('success', 'Mesaj taslağı güncellendi');

        } catch (\Exception $e) {
            Log::error('Mesaj taslağı güncelleme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Güncelleme sırasında hata oluştu: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Güncelleme sırasında hata oluştu');
        }
    }

    /**
     * Mesaj taslağını sil
     */
    public function destroy($id)
    {
        try {
            $message = AIMessage::findOrFail($id);
            $message->delete();

            return redirect()->route('admin.ai.mesaj-taslaklari.index')
                ->with('success', 'Mesaj taslağı silindi');

        } catch (\Exception $e) {
            Log::error('Mesaj taslağı silme hatası', [
                'error' => $e->getMessage(),
                'message_id' => $id,
            ]);

            return redirect()->back()->with('error', 'Silme sırasında hata oluştu');
        }
    }
}
