<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\AI\NLPProcessor;
use App\Services\LeadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Instagram Direct Message Webhook Controller
 *
 * Handles incoming DM messages from Instagram Business Platform
 *
 * Webhook URL: POST /api/v1/webhook/instagram
 *
 * Integration Flow:
 * Instagram DM → Webhook → NLPProcessor → Response
 *
 * Setup:
 * 1. Create Instagram Business Account at https://business.instagram.com/
 * 2. Connect to Facebook Business Account
 * 3. Get App ID from Meta App Dashboard
 * 4. Configure webhook in App Dashboard
 * 5. Add credentials to .env:
 *    - INSTAGRAM_WEBHOOK_TOKEN
 *    - INSTAGRAM_BUSINESS_ACCOUNT_ID
 *    - INSTAGRAM_ACCESS_TOKEN
 */
class InstagramWebhookController extends Controller
{
    protected NLPProcessor $nlp;

    public function __construct(NLPProcessor $nlp)
    {
        $this->nlp = $nlp;
    }

    /**
     * Handle Instagram webhook POST (incoming messages)
     *
     * Instagram sends DM webhook data in this format:
     * {
     *   "object": "instagram",
     *   "entry": [{
     *     "id": "...",
     *     "messaging": [{
     *       "sender": { "id": "123456" },
     *       "recipient": { "id": "789012" },
     *       "timestamp": 1234567890,
     *       "message": {
     *         "mid": "msg_id",
     *         "text": "Bodrum'da 3+1 daire arıyorum",
     *         "attachments": [...]
     *       }
     *     }]
     *   }]
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Validate webhook signature
            if (!$this->validateSignature($request)) {
                Log::warning('Instagram webhook signature validation failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->json()->all();

            // Instagram sends read receipts and typing indicators - acknowledge but don't process
            if ($this->isStatusUpdate($data)) {
                return response()->json(['success' => true], 200);
            }

            // Extract message from webhook payload
            $message = $this->extractMessage($data);

            if (!$message) {
                Log::info('Instagram webhook received with no extractable message');
                return response()->json(['success' => true], 200);
            }

            // Process message with NLP
            $this->processMessage($message);

            // Always return 200 OK to Instagram (async processing)
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Instagram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    /**
     * Instagram webhook verification (GET request)
     *
     * Instagram Platform sends GET request to verify webhook:
     * GET /api/v1/webhook/instagram?hub.mode=subscribe&hub.challenge=abc123&hub.verify_token=my_token
     *
     * @param Request $request
     * @return string|JsonResponse
     */
    public function verifyWebhook(Request $request)
    {
        // Laravel converts dots to underscores in query params
        $mode = $request->input('hub.mode') ?? $request->input('hub_mode');
        $token = $request->input('hub.verify_token') ?? $request->input('hub_verify_token');
        $challenge = $request->input('hub.challenge') ?? $request->input('hub_challenge');

        // Verify mode
        if ($mode !== 'subscribe') {
            Log::warning('Instagram webhook verification: invalid mode', ['mode' => $mode]);
            return response()->json(['error' => 'Invalid mode'], 403);
        }

        // Verify token matches configured token
        if ($token !== config('services.instagram.webhook_verify_token')) {
            Log::warning('Instagram webhook verification: invalid token');
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // Return challenge to complete verification
        Log::info('Instagram webhook verified successfully');
        return $challenge;
    }

    /**
     * Validate webhook signature
     *
     * Instagram signs POST requests with HMAC SHA-256 using the App Secret.
     * Signature header: X-Hub-Signature-256 = sha256=<hex_hash>
     *
     * @param Request $request
     * @return bool
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::channel('security')->warning('Instagram webhook: missing X-Hub-Signature-256 header');
            return false;
        }

        // Format: sha256=hex_hash
        $parts = explode('=', $signature, 2);
        if (count($parts) !== 2 || $parts[0] !== 'sha256') {
            Log::channel('security')->warning('Instagram webhook: malformed signature header');
            return false;
        }

        $hash = $parts[1];
        $appSecret = config('services.instagram.app_secret');

        if (empty($appSecret)) {
            Log::channel('security')->error('Instagram webhook: app_secret not configured');
            return false;
        }

        $payload = $request->getContent();
        $expectedHash = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedHash, $hash);
    }

    /**
     * Check if webhook payload is a status update (not a message)
     *
     * Instagram sends: read receipts, typing indicators, delivery confirmations
     * We acknowledge these but don't process them
     *
     * @param array $data
     * @return bool
     */
    protected function isStatusUpdate(array $data): bool
    {
        if (!isset($data['entry'][0]['messaging'][0])) {
            return true; // Unknown format, treat as status
        }

        $messaging = $data['entry'][0]['messaging'][0];

        // Has message = user sent message
        if (isset($messaging['message']) && !empty($messaging['message'])) {
            return false;
        }

        // Has delivery/read/typing = status update
        if (isset($messaging['delivery']) || isset($messaging['read']) || isset($messaging['typing_on'])) {
            return true;
        }

        return true; // Unknown, treat as status
    }

    /**
     * Extract message text from webhook payload
     *
     * Handles:
     * - Text messages
     * - Image messages with caption
     * - Video messages with caption
     * - File/document attachments with caption
     * - Other message types (returns null)
     *
     * @param array $data
     * @return array|null { text, from_id, from_username, message_id, timestamp }
     */
    protected function extractMessage(array $data): ?array
    {
        try {
            if (!isset($data['entry'][0]['messaging'][0])) {
                return null;
            }

            $messaging = $data['entry'][0]['messaging'][0];
            $message = $messaging['message'] ?? null;

            if (!$message) {
                return null;
            }

            $text = null;

            // Extract text based on message type
            if (isset($message['text'])) {
                $text = $message['text'];
            } elseif (isset($message['attachments'])) {
                // Extract caption from attachment if available
                $attachment = $message['attachments'][0] ?? null;
                if ($attachment && isset($attachment['title'])) {
                    $text = $attachment['title'];
                } elseif ($attachment && isset($attachment['payload']['title'])) {
                    $text = $attachment['payload']['title'];
                }
            }

            if (!$text) {
                return null;
            }

            return [
                'text' => $text,
                'from_id' => $messaging['sender']['id'] ?? null,
                'from_username' => $messaging['sender']['email'] ?? null, // May not be available
                'message_id' => $message['mid'] ?? null,
                'timestamp' => $messaging['timestamp'] ?? null,
                'has_attachments' => isset($message['attachments']),
            ];

        } catch (\Exception $e) {
            Log::error('Error extracting Instagram message', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Process incoming message
     *
     * 1. Parse with NLPProcessor
     * 2. Send acknowledgment
     * 3. Create lead/inquiry in database
     * 4. Optionally send auto-reply
     *
     * @param array $message
     * @return void
     */
    protected function processMessage(array $message): void
    {
        try {
            // 1. Parse message with NLP
            $parsed = $this->nlp->parseMessage($message['text']);

            // 2. Create/update lead in database
            $leadService = app(\App\Services\LeadService::class);
            $lead = $leadService->createOrUpdateFromWebhook(
                'instagram',
                $message['from_id'],
                $message['text'],
                $parsed,
                [
                    'username' => $message['from_username'] ?? null,
                ]
            );

            // 3. Log for analytics
            Log::info('Instagram message processed and stored', [
                'lead_id' => $lead->id,
                'from_id' => $message['from_id'],
                'intent' => $parsed['intent'],
                'confidence' => $parsed['confidence'],
                'has_attachments' => $message['has_attachments'],
            ]);

            // 4. Send auto-reply
            $replyText = $parsed['response'] ?? 'Mesajınız kaydedildi. Danışmanlarımız kısa sürede sizinle iletişime geçecekler.';
            self::sendMessage($message['from_id'], $replyText);

            // 5. Trigger CRM workflow if qualified
            if ($lead->confidence >= 0.70) {
                // Auto-qualify if high confidence
                $leadService->qualifyLead($lead);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Instagram message', [
                'error' => $e->getMessage(),
                'from_id' => $message['from_id'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Send Instagram Direct Message to user
     *
     * Uses Instagram Graph API to send message
     *
     * @param string $recipientId - Recipient Instagram user ID
     * @param string $message - Message text to send
     * @return bool
     */
    public static function sendMessage(string $recipientId, string $message): bool
    {
        try {
            $businessAccountId = config('services.instagram.business_account_id');
            $accessToken = config('services.instagram.access_token');
            $apiVersion = config('services.instagram.api_version', 'v18.0');

            // Instagram uses Facebook Graph API for messaging
            $url = "https://graph.instagram.com/$apiVersion/$businessAccountId/messages";

            $response = Http::withToken($accessToken)->post($url, [
                'recipient' => [
                    'id' => $recipientId,
                ],
                'message' => [
                    'text' => $message,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Instagram message', [
                    'recipient_id' => $recipientId,
                    'status' => $response->status(), // context7-ignore
                    'error' => $response->json(),
                ]);
                return false;
            }

            Log::info('Instagram message sent', ['recipient_id' => $recipientId]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error sending Instagram message', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);
            return false;
        }
    }
}
