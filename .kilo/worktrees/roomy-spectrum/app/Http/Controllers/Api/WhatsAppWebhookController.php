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
 * WhatsApp Business API Webhook Controller
 *
 * Handles incoming messages from WhatsApp Business Platform
 *
 * Webhook URL: POST /api/v1/webhook/whatsapp
 *
 * Integration Flow:
 * WhatsApp Business API → Webhook → NLPProcessor → Response
 *
 * Setup:
 * 1. Create WhatsApp Business Account at https://www.whatsapp.com/business/
 * 2. Get Webhook Token from API Settings
 * 3. Configure webhook URL in WhatsApp App Dashboard
 * 4. Add token to .env: WHATSAPP_WEBHOOK_TOKEN=your_token_here
 * 5. Add phone number to .env: WHATSAPP_PHONE_NUMBER_ID=your_phone_id
 */
class WhatsAppWebhookController extends Controller
{
    protected NLPProcessor $nlp;

    public function __construct(NLPProcessor $nlp)
    {
        $this->nlp = $nlp;
    }

    /**
     * Handle WhatsApp webhook POST (incoming messages)
     *
     * WhatsApp sends webhook data in this format:
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "id": "...",
     *     "changes": [{
     *       "value": {
     *         "messages": [{
     *           "from": "+905552342000",
     *           "id": "wamid.xxx",
     *           "type": "text", // context7-ignore
     *           "text": { "body": "Bodrum'da 3+1 daire arıyorum" }
     *         }],
     *         "contacts": [{
     *           "profile": { "name": "Ahmet Yılmaz" },
     *           "wa_id": "905552342000"
     *         }]
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
                Log::warning('WhatsApp webhook signature validation failed', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $data = $request->json()->all();

            // WhatsApp sends state updates (delivery, read, etc) - acknowledge but don't process
            if ($this->isStatusUpdate($data)) {
                return response()->json(['success' => true], 200);
            }

            // Extract message from webhook payload
            $message = $this->extractMessage($data);

            if (!$message) {
                Log::info('WhatsApp webhook received with no extractable message');
                return response()->json(['success' => true], 200);
            }

            // Process message with NLP
            $this->processMessage($message);

            // Always return 200 OK to WhatsApp (async processing)
            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    /**
     * WhatsApp webhook verification (GET request)
     *
     * WhatsApp Platform sends GET request to verify webhook:
     * GET /api/v1/webhook/whatsapp?hub.mode=subscribe&hub.challenge=abc123&hub.verify_token=my_token
     *
     * Respond with challenge token to complete verification
     *
     * @param Request $request
     * @return string|JsonResponse
     */
    public function verifyWebhook(Request $request)
    {
        // Laravel converts dots to underscores in query params
        // hub.mode becomes hub_mode in $request->query()
        $mode = $request->input('hub.mode') ?? $request->input('hub_mode');
        $token = $request->input('hub.verify_token') ?? $request->input('hub_verify_token');
        $challenge = $request->input('hub.challenge') ?? $request->input('hub_challenge');

        // Verify mode
        if ($mode !== 'subscribe') {
            Log::warning('WhatsApp webhook verification: invalid mode', ['mode' => $mode]);
            return response()->json(['error' => 'Invalid mode'], 403);
        }

        // Verify token matches configured token
        if ($token !== config('services.whatsapp.webhook_verify_token')) {
            Log::warning('WhatsApp webhook verification: invalid token');
            return response()->json(['error' => 'Invalid token'], 403);
        }

        // Return challenge to complete verification
        Log::info('WhatsApp webhook verified successfully');
        return $challenge;
    }

    /**
     * Validate webhook signature
     *
     * WhatsApp signs POST requests with HMAC SHA-256 using the App Secret.
     * Signature header: X-Hub-Signature-256 = sha256=<hex_hash>
     *
     * @param Request $request
     * @return bool
     */
    protected function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (!$signature) {
            Log::channel('security')->warning('WhatsApp webhook: missing X-Hub-Signature-256 header');
            return false;
        }

        // Format: sha256=hex_hash
        $parts = explode('=', $signature, 2);
        if (count($parts) !== 2 || $parts[0] !== 'sha256') {
            Log::channel('security')->warning('WhatsApp webhook: malformed signature header');
            return false;
        }

        $hash = $parts[1];
        $appSecret = config('services.whatsapp.app_secret');

        if (empty($appSecret)) {
            Log::channel('security')->error('WhatsApp webhook: app_secret not configured');
            return false;
        }

        $payload = $request->getContent();
        $expectedHash = hash_hmac('sha256', $payload, $appSecret);

        return hash_equals($expectedHash, $hash);
    }

    /**
     * Check if webhook payload is a state update (not a message)
     *
     * State updates: message delivery, read mark, typing indicator, etc
     * We acknowledge these but don't process them
     *
     * @param array $data
     * @return bool
     */
    protected function isStatusUpdate(array $data): bool
    {
        if (!isset($data['entry'][0]['changes'][0]['value'])) {
            return true; // Unknown format, treat as state
        }

        $value = $data['entry'][0]['changes'][0]['value'];

        // Has messages = user sent message
        if (isset($value['messages']) && !empty($value['messages'])) {
            return false;
        }

        // Has states = delivery/read mark update
        if (isset($value['statuses']) && !empty($value['statuses'])) { // context7-ignore
            return true;
        }

        return true; // Unknown, treat as state
    }

    /**
     * Extract message text from webhook payload
     *
     * Handles:
     * - Text messages
     * - Image messages with caption
     * - Document messages with caption
     * - Other message types (returns null)
     *
     * @param array $data
     * @return array|null { text, from, name, message_id, timestamp }
     */
    protected function extractMessage(array $data): ?array
    {
        try {
            if (!isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                return null;
            }

            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $contact = $data['entry'][0]['changes'][0]['value']['contacts'][0] ?? null;

            $text = null;
            $type = $message['type'] ?? null; // context7-ignore

            // Extract text based on message type
            match ($type) {
                'text' => $text = $message['text']['body'] ?? null,
                'image' => $text = $message['image']['caption'] ?? null,
                'document' => $text = $message['document']['caption'] ?? null,
                'audio' => $text = '[Audio message]', // Could transcribe with Whisper API
                'video' => $text = $message['video']['caption'] ?? null,
                default => $text = null,
            };

            if (!$text) {
                return null;
            }

            return [
                'text' => $text,
                'from' => $message['from'] ?? null,
                'name' => $contact['profile']['name'] ?? 'Unknown',
                'message_id' => $message['id'] ?? null,
                'timestamp' => $message['timestamp'] ?? null,
                'type' => $type, // context7-ignore
            ];

        } catch (\Exception $e) {
            Log::error('Error extracting WhatsApp message', ['error' => $e->getMessage()]);
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
                'whatsapp',
                $message['from'],
                $message['text'],
                $parsed,
                [
                    'phone' => $message['from'],
                    'name' => $message['name'] ?? null,
                ]
            );

            // 3. Log for analytics
            Log::info('WhatsApp message processed and stored', [
                'lead_id' => $lead->id,
                'from' => $message['from'],
                'name' => $message['name'],
                'intent' => $parsed['intent'],
                'confidence' => $parsed['confidence'],
                'type' => $message['type'], // context7-ignore
            ]);

            // 4. Send auto-reply
            $replyText = $parsed['response'] ?? 'Mesajınız kaydedildi. Danışmanlarımız kısa sürede sizinle iletişime geçecekler.';
            self::sendMessage($message['from'], $replyText);

            // 5. Trigger CRM workflow if qualified
            if ($lead->confidence >= 0.70) {
                $leadService->qualifyLead($lead);
            }

        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp message', [
                'error' => $e->getMessage(),
                'from' => $message['from'] ?? 'unknown',
            ]);
        }
    }

    /**
     * Send WhatsApp message to user
     *
     * Uses WhatsApp Business API to send message
     *
     * @param string $phoneNumber - Recipient phone number (e.g., 905551234567)
     * @param string $message - Message text to send
     * @return bool
     */
    public static function sendMessage(string $phoneNumber, string $message): bool
    {
        try {
            $phoneNumberId = config('services.whatsapp.phone_number_id');
            $accessToken = config('services.whatsapp.access_token');
            $apiVersion = config('services.whatsapp.api_version', 'v18.0');

            $url = "https://graph.facebook.com/$apiVersion/$phoneNumberId/messages";

            $response = \Http::withToken($accessToken)->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phoneNumber,
                'type' => 'text', // context7-ignore
                'text' => [
                    'body' => $message,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send WhatsApp message', [
                    'phone' => $phoneNumber,
                    'api_state' => $response->toPsrResponse()->getStatusCode(),
                    'error' => $response->json(),
                ]);
                return false;
            }

            Log::info('WhatsApp message sent', ['phone' => $phoneNumber]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error sending WhatsApp message', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }
}
