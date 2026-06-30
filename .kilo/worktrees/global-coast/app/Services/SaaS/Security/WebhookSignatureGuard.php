<?php

namespace App\Services\SaaS\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WebhookSignatureGuard
 * 🛡️ SAB §12.4: Webhook Cognitive Verification
 */
class WebhookSignatureGuard
{
    /**
     * Verify Stripe Webhook Signature
     */
    public function verifyStripe(Request $request): bool
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            return true;
        } catch (\Exception $e) {
            Log::critical("STRIPE_WEBHOOK_VERIFICATION_FAILED", [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);
            return false;
        }
    }

    /**
     * Verify Iyzico Webhook Signature
     */
    public function verifyIyzico(Request $request): bool
    {
        // Iyzico specific signature verification logic
        // Typically involves HMAC-SHA256 of payload with API Secret
        $iyziSignature = $request->header('X-IYZI-SIGNATURE');
        $payload = $request->getContent();
        $secret = config('services.iyzico.secret_key');

        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        if (!hash_equals($expectedSignature, $iyziSignature)) {
            Log::critical("IYZICO_WEBHOOK_VERIFICATION_FAILED", [
                'ip' => $request->ip()
            ]);
            return false;
        }

        return true;
    }
}
