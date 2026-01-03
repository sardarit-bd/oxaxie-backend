<?php

namespace App\Services\PaymentGateways;

use App\Contracts\OnlinePaymentInterface;
use App\Contracts\RefundablePaymentInterface;
use App\Contracts\WebhookSupportInterface;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripePaymentGateway implements 
    OnlinePaymentInterface, 
    RefundablePaymentInterface,
    WebhookSupportInterface
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Check if gateway is available and configured
     */
    public function isAvailable(): bool
    {
        return !empty(config('services.stripe.secret')) && 
               !empty(config('services.stripe.key'));
    }

    /**
     * Get gateway name
     */
    public function getGatewayName(): string
    {
        return 'Stripe';
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $transactionId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'payment_method' => $paymentIntent->payment_method,
                'created' => $paymentIntent->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to get Stripe payment status: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create payment intent for online payment
     */
    public function createPaymentIntent(array $data): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $data['amount'], // Amount in cents
                'currency' => $data['currency'] ?? 'usd',
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? [],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve a payment intent
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'payment_method' => $paymentIntent->payment_method,
                'client_secret' => $paymentIntent->client_secret,
                'metadata' => $paymentIntent->metadata->toArray(),
                'created' => $paymentIntent->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe payment intent: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirm a payment
     */
    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            // Retrieve the payment intent from Stripe
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            // Check if payment was successful
            if ($paymentIntent->status === 'succeeded') {
                return [
                    'success' => true,
                    'status' => 'succeeded',
                    'amount' => $paymentIntent->amount,
                    'currency' => $paymentIntent->currency,
                    'payment_method' => $paymentIntent->payment_method,
                    'metadata' => $paymentIntent->metadata->toArray(),
                    'created' => $paymentIntent->created,
                ];
            }
            
            // Handle other statuses
            $statusMessages = [
                'requires_payment_method' => 'Payment requires a payment method',
                'requires_confirmation' => 'Payment requires confirmation',
                'requires_action' => 'Payment requires additional action (e.g., 3D Secure)',
                'processing' => 'Payment is processing',
                'requires_capture' => 'Payment requires capture',
                'canceled' => 'Payment was canceled',
            ];
            
            $message = $statusMessages[$paymentIntent->status] ?? 'Payment not completed';
            
            return [
                'success' => false,
                'error' => $message . '. Status: ' . $paymentIntent->status,
                'status' => $paymentIntent->status,
            ];
            
        } catch (ApiErrorException $e) {
            Log::error('Stripe payment confirmation failed: ' . $e->getMessage(), [
                'payment_intent_id' => $paymentIntentId,
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment confirmation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $paymentIntentId, ?array $additionalData = null): array
    {
        return $this->confirmPayment($paymentIntentId);
    }

    /**
     * Check if payment can be refunded
     */
    public function canRefund(string $transactionId): bool
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);
            return $paymentIntent->status === 'succeeded' && 
                   $paymentIntent->amount > 0;
        } catch (ApiErrorException $e) {
            Log::error('Error checking refund eligibility: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $transactionId, ?float $amount = null): array
    {
        try {
            $refundData = ['payment_intent' => $transactionId];

            if ($amount !== null) {
                $refundData['amount'] = (int)($amount * 100);
            }
            
            $refund = $this->stripe->refunds->create($refundData);
            
            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
                'currency' => $refund->currency,
                'status' => $refund->status,
                'reason' => $refund->reason,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $webhookSecret = config('services.stripe.webhook_secret');
            
            if (empty($webhookSecret)) {
                Log::warning('Stripe webhook secret not configured');
                return false;
            }

            \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
            
            return true;
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle webhook payload
     */
    public function handleWebhook(array $payload): array
    {
        try {
            $eventType = $payload['type'] ?? null;
            
            if (!$eventType) {
                return [
                    'success' => false,
                    'error' => 'No event type found in webhook payload',
                ];
            }

            Log::info('Stripe webhook received', ['event' => $eventType]);

            // Handle different event types
            return match($eventType) {
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($payload['data']['object']),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($payload['data']['object']),
                'charge.refunded' => $this->handleChargeRefunded($payload['data']['object']),
                default => [
                    'success' => true,
                    'event_type' => $eventType,
                    'message' => 'Event acknowledged but not processed',
                ]
            };
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment intent succeeded event
     */
    protected function handlePaymentIntentSucceeded(array $paymentIntent): array
    {
        return [
            'success' => true,
            'event_type' => 'payment_intent.succeeded',
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
        ];
    }

    /**
     * Handle payment intent failed event
     */
    protected function handlePaymentIntentFailed(array $paymentIntent): array
    {
        return [
            'success' => true,
            'event_type' => 'payment_intent.payment_failed',
            'payment_intent_id' => $paymentIntent['id'],
            'error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
        ];
    }

    /**
     * Handle charge refunded event
     */
    protected function handleChargeRefunded(array $charge): array
    {
        return [
            'success' => true,
            'event_type' => 'charge.refunded',
            'charge_id' => $charge['id'],
            'amount_refunded' => $charge['amount_refunded'],
            'currency' => $charge['currency'],
        ];
    }
}