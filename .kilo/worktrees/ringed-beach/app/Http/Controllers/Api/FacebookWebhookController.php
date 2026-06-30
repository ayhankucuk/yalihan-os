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
 * Facebook Messenger Webhook Controller
 *
 * Handles incoming messages from Facebook Messenger Platform
 *
 * Webhook URL: POST /api/v1/webhook/facebook
 *
 * Integration Flow:
 * Facebook Messenger → Webhook → NLPProcessor → Response
 *
 * Setup:
 * 1. Create Facebook Business Account at https://business.facebook.com/
 * 2. Create/configure Facebook Page
 * 3. Create App in Meta App Manager
 * 4. Configure webhook in App Dashboard
 * 5. Subscribe to page_messaging webhook events
 * 6. Add credentials to .env:
 *    - FACEBOOK_WEBHOOK_TOKEN
 *    - FACEBOOK_PAGE_ACCESS_TOKEN
 *    - FACEBOOK_PAGE_ID
 */
class FacebookWebhookController extends Controller
{
    protected NLPProcessor $nlp;

    public function __construct(NLPProcessor $nlp)
    {
        $this->nlp = $nlp;
    }

    /**
     * Handle Facebook webhook POST (incoming messages)
     *
     * Facebook sends webhook data in this format:
     * {
     *   "object": "page",
     *   "entry": [{
     *     "id": "page_id",
     *     "time": 1234567890,
     *     "messaging": [{
     *       "sender": { "id": "user_id" },
     *       "recipient": { "id": "page_id" },
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
                Log::warning('Facebook webhook signature validation failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->json()->all();

            // Facebook sends postback, delivery, read events - acknowledge but don't process
            if ($this->isStatusUpdate($data)) {
                return response()->json(['success' => true], 200);
            }

            // Extract message from webhook payload
            $message = $this->extractMessage($data);

            if (!$message) {
                Log::info('Facebook webhook received with no extractable message');
                return response()->json(['success' => true], 200);
            }

            // Process message with NLP
            $this->processMessage($message);

            // Always return 200 OK to Facebook (async processing)
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Facebook webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    /**
     * Facebook webhook verification (GET request)
     *
     * Facebook Platform sends GET request to verify webhook:
     * GET /api/v1/webhook/facebook?hub.mode=subscribe&hub.challenge=abc123&hub.verify_token=my_token
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
            Log::warning('Facebook webhook verification: invalid mode', ['mode' => $mode]);
            return response()->json(['error' => 'Invalid mode'], 403);
        }

        // Verify token matches configured token
        if ($token !== config('services.facebook.webhook_verify_token')) {
            Log::warning('Facebook webhook verification: invalid token');
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // Return challenge to complete verification
        Log::info('Facebook webhook verified successfully');
        return $challenge;
    }

    /**
     * Validate webhook signature
     *
     * Facebook signs POST requests with HMAC SHA-256 using the App Secret.
     * Signature header: X-Hub-Signature-256 = sha256=<hex_hash>
     *
     * @param Request $request
     * @return bool
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::channel('security')->warning('Facebook webhook: missing X-Hub-Signature-256 header');
            return false;
        }

        // Format: sha256=hex_hash
        $parts = explode('=', $signature, 2);
        if (count($parts) !== 2 || $parts[0] !== 'sha256') {
            Log::channel('security')->warning('Facebook webhook: malformed signature header');
            return false;
        }

        $hash = $parts[1];
        $appSecret = config('services.facebook.app_secret');

        if (empty($appSecret)) {
            Log::channel('security')->error('Facebook webhook: app_secret not configured');
            return false;
        }

        $payload = $request->getContent();
        $expectedHash = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedHash, $hash);
    }

    /**
     * Check if webhook payload is a status update (not a message)
     *
     * Facebook sends: read receipts, delivery confirmations, postbacks, quick replies
     * We acknowledge these but don't process them as messages
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

        // Has postback = user clicked button (treat as message)
        if (isset($messaging['postback'])) {
            return false;
        }

        // Has quick_reply = user selected quick reply option (treat as message)
        if (isset($messaging['quick_reply'])) {
            return false;
        }

        // Has delivery/read = status update
        if (isset($messaging['delivery']) || isset($messaging['read'])) {
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
     * - File/document attachments
     * - Postback (button clicks)
     * - Quick reply selections
     * - Other message types (returns null)
     *
     * @param array $data
     * @return array|null { text, from_id, first_name, message_id, timestamp }
     */
    protected function extractMessage(array $data): ?array
    {
        try {
            if (!isset($data['entry'][0]['messaging'][0])) {
                return null;
            }

            $messaging = $data['entry'][0]['messaging'][0];
            $text = null;

            // Extract text based on message type
            if (isset($messaging['message'])) {
                $message = $messaging['message'];

                if (isset($message['text'])) {
                    $text = $message['text'];
                } elseif (isset($message['attachments'])) {
                    // Extract URL from attachment (image, video, file)
                    $attachment = $message['attachments'][0] ?? null;
                    if ($attachment && isset($attachment['payload']['url'])) {
                        $text = '[Attachment: ' . $attachment['type'] . '] ' . $attachment['payload']['url']; // context7-ignore
                    }
                }
            } elseif (isset($messaging['postback'])) {
                // Button click postback
                $text = $messaging['postback']['title'] ?? null;
            } elseif (isset($messaging['quick_reply'])) {
                // Quick reply selection
                $text = $messaging['quick_reply']['payload'] ?? null;
            }

            if (!$text) {
                return null;
            }

            return [
                'text' => $text,
                'from_id' => $messaging['sender']['id'] ?? null,
                'first_name' => null, // Facebook doesn't send name in webhook, need to fetch via API
                'message_id' => $messaging['message']['mid'] ?? null,
                'timestamp' => $messaging['timestamp'] ?? null,
                'is_postback' => isset($messaging['postback']),
                'is_quick_reply' => isset($messaging['quick_reply']),
            ];

        } catch (\Exception $e) {
            Log::error('Error extracting Facebook message', ['error' => $e->getMessage()]);
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
                'facebook',
                $message['from_id'],
                $message['text'],
                $parsed,
                [
                    'username' => $message['from_name'] ?? null,
                ]
            );

            // 3. Log for analytics
            Log::info('Facebook message processed and stored', [
                'lead_id' => $lead->id,
                'from_id' => $message['from_id'],
                'intent' => $parsed['intent'],
                'confidence' => $parsed['confidence'],
                'is_postback' => $message['is_postback'],
                'is_quick_reply' => $message['is_quick_reply'],
            ]);

            // 4. Send auto-reply with quick replies (Phase 4)
            $replyText = $parsed['response'] ?? 'Mesajınız kaydedildi. Danışmanlarımız kısa sürede sizinle iletişime geçecekler.';
            self::sendMessage($message['from_id'], $replyText);

            // 5. Trigger CRM workflow if qualified
            if ($lead->confidence >= 0.70) {
                $leadService->qualifyLead($lead);
            }

        } catch (\Exception $e) {
            Log::error('Error processing Facebook message', [
                'error' => $e->getMessage(),
                'from_id' => $message['from_id'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Send Facebook Messenger message to user
     *
     * Uses Facebook Graph API to send message
     *
     * @param string $recipientId - Recipient Facebook user ID
     * @param string $message - Message text to send
     * @param array $quickReplies - Optional quick reply buttons
     * @return bool
     */
    public static function sendMessage(string $recipientId, string $message, array $quickReplies = []): bool
    {
        try {
            $pageAccessToken = config('services.facebook.page_access_token');
            $apiVersion = config('services.facebook.api_version', 'v18.0');

            $url = "https://graph.facebook.com/$apiVersion/me/messages";

            $payload = [
                'recipient' => [
                    'id' => $recipientId,
                ],
                'message' => [
                    'text' => $message,
                ],
            ];

            // Add quick replies if provided
            if (!empty($quickReplies)) {
                $payload['message']['quick_replies'] = array_map(function ($reply) {
                    return [
                        'content_type' => 'text',
                        'title' => $reply['title'] ?? $reply,
                        'payload' => $reply['payload'] ?? $reply,
                    ];
                }, $quickReplies);
            }

            $response = Http::withToken($pageAccessToken)->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Failed to send Facebook message', [
                    'recipient_id' => $recipientId,
                    'status' => $response->status(), // context7-ignore
                    'error' => $response->json(),
                ]);
                return false;
            }

            Log::info('Facebook message sent', ['recipient_id' => $recipientId]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error sending Facebook message', [
                'error' => $e->getMessage(),
                'recipient_id' => $recipientId,
            ]);
            return false;
        }
    }
}
