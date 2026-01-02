<?php

namespace App\Services\PaymentGateways;

use App\Contracts\OnlinePaymentInterface;
use App\Contracts\RefundablePaymentInterface;
use App\Contracts\WebhookSupportInterface;
use App\Contracts\RecurringPaymentInterface;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripePaymentGateway implements 
    OnlinePaymentInterface, 
    RefundablePaymentInterface,
    WebhookSupportInterface,
    RecurringPaymentInterface
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    // Base PaymentGatewayInterface methods
    
    public function processPayment(array $data): array
    {
        // For Stripe, processing means creating and confirming a payment intent
        $intent = $this->createPaymentIntent($data);
        
        if ($intent['success'] && isset($data['auto_confirm']) && $data['auto_confirm']) {
            return $this->confirmPayment($intent['payment_intent_id']);
        }
        
        return $intent;
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return $this->retrievePaymentIntent($transactionId);
    }

    public function getGatewayName(): string
    {
        return 'stripe';
    }

    public function isAvailable(): bool
    {
        return !empty(config('services.stripe.secret'));
    }

    // OnlinePaymentInterface methods
    
    public function createPaymentIntent(array $data): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $this->convertToSmallestUnit($data['amount'], $data['currency'] ?? 'usd'),
                'currency' => $data['currency'] ?? 'usd',
                'metadata' => $data['metadata'] ?? [],
                'description' => $data['description'] ?? null,
                'payment_method_types' => $data['payment_method_types'] ?? ['card'],
            ]);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

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
                'metadata' => $paymentIntent->metadata->toArray(),
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function confirmPayment(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->confirm($paymentIntentId);

            return [
                'success' => true,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // RefundablePaymentInterface methods
    
    public function refundPayment(string $transactionId, ?float $amount = null): array
    {
        try {
            $refundData = ['payment_intent' => $transactionId];
            
            if ($amount !== null) {
                $refundData['amount'] = $this->convertToSmallestUnit($amount, 'usd');
            }

            $refund = $this->stripe->refunds->create($refundData);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'status' => $refund->status,
                'amount' => $refund->amount,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function canRefund(string $transactionId): bool
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);
            return $paymentIntent->status === 'succeeded';
        } catch (ApiErrorException $e) {
            return false;
        }
    }

    // WebhookSupportInterface methods
    
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
            return true;
        } catch (SignatureVerificationException $e) {
            return false;
        }
    }

    public function parseWebhookPayload(string $payload): array
    {
        try {
            $event = json_decode($payload, true);
            return [
                'success' => true,
                'event_type' => $event['type'] ?? null,
                'data' => $event['data']['object'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // RecurringPaymentInterface methods
    
    public function createSubscription(array $data): array
    {
        try {
            $subscription = $this->stripe->subscriptions->create([
                'customer' => $data['customer_id'],
                'items' => $data['items'],
                'metadata' => $data['metadata'] ?? [],
            ]);

            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            $subscription = $this->stripe->subscriptions->cancel($subscriptionId);

            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getSubscription(string $subscriptionId): array
    {
        try {
            $subscription = $this->stripe->subscriptions->retrieve($subscriptionId);

            return [
                'success' => true,
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // Helper methods
    
    protected function convertToSmallestUnit(float $amount, string $currency): int
    {
        $zeroDecimalCurrencies = ['jpy', 'krw', 'vnd', 'clp'];
        
        if (in_array(strtolower($currency), $zeroDecimalCurrencies)) {
            return (int) $amount;
        }

        return (int) ($amount * 100);
    }
}