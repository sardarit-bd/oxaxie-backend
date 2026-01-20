<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\CreditPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected CreditPurchaseService $creditPurchaseService
    ) {}

    /**
     * Handle Stripe webhook
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Webhook invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        // Handle the event
        switch ($event->type) {
            // ✅ NEW: Handle credit purchases
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutSessionCompleted($session);
                break;

            // ✅ EXISTING: Your current payment intent handlers
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'succeeded'
                );
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'failed'
                );
                break;

            case 'payment_intent.canceled':
                $paymentIntent = $event->data->object;
                $this->paymentService->updatePaymentStatus(
                    $paymentIntent->id,
                    'canceled'
                );
                break;

            case 'charge.refunded':
                $charge = $event->data->object;
                if (isset($charge->payment_intent)) {
                    $this->paymentService->updatePaymentStatus(
                        $charge->payment_intent,
                        'refunded'
                    );
                }
                break;

            default:
                Log::info('Unhandled webhook event type', ['type' => $event->type]);
                return response()->json(['message' => 'Event type not handled'], 200);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle completed checkout session (for credit purchases)
     */
    protected function handleCheckoutSessionCompleted($session): void
    {
        try {
            $metadata = $session->metadata;
            
            Log::info('Processing checkout session', [
                'session_id' => $session->id,
                'payment_status' => $session->payment_status,
                'metadata' => $metadata,
            ]);

            // Only process if payment was successful
            if ($session->payment_status !== 'paid') {
                Log::warning('Checkout session not paid yet', [
                    'session_id' => $session->id,
                    'status' => $session->payment_status,
                ]);
                return;
            }

            // Check if this is a credit purchase
            if (isset($metadata->type) && $metadata->type === 'credit_purchase') {
                $this->createCreditPurchaseFromSession($session, $metadata);
            } else {
                // Not a credit purchase - could be subscription or other payment
                Log::info('Checkout session completed (not a credit purchase)', [
                    'session_id' => $session->id,
                    'metadata_type' => $metadata->type ?? 'none',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle checkout session', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Create credit purchase from checkout session
     */
    protected function createCreditPurchaseFromSession($session, $metadata): void
    {
        $userId = $metadata->user_id;
        $subscriptionId = $metadata->subscription_id;
        $creditsAmount = $metadata->credits_amount;
        $expiresAt = $metadata->expires_at;

        Log::info('Creating credit purchase', [
            'user_id' => $userId,
            'subscription_id' => $subscriptionId,
            'credits_amount' => $creditsAmount,
            'session_id' => $session->id,
            'payment_intent' => $session->payment_intent,
        ]);

        // Check if already processed (prevent duplicates)
        $existing = \App\Models\CreditPurchase::where('stripe_payment_intent_id', $session->payment_intent)
            ->first();

        if ($existing) {
            Log::warning('Credit purchase already exists', [
                'credit_purchase_id' => $existing->id,
                'payment_intent' => $session->payment_intent,
            ]);
            return;
        }

        try {
            // Create credit purchase with subscription_id
            $creditPurchase = $this->creditPurchaseService->createPurchase($userId, [
                'subscription_id' => $subscriptionId,
                'amount' => $session->amount_total / 100,
                'credits_added' => $creditsAmount,
                'expires_at' => $expiresAt,
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);

            // Mark as completed
            $this->creditPurchaseService->updateStatus(
                $userId,
                $creditPurchase->id,
                'completed'
            );

            Log::info('✅ Credit purchase created and completed', [
                'credit_purchase_id' => $creditPurchase->id,
                'user_id' => $userId,
                'subscription_id' => $subscriptionId,
                'credits' => $creditsAmount,
                'amount_paid' => $session->amount_total / 100,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to create credit purchase', [
                'user_id' => $userId,
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}