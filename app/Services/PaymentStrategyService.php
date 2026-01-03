<?php

namespace App\Services;

use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use App\Factories\PaymentGatewayFactory;
use App\Contracts\ManualPaymentInterface;
use App\Contracts\OnlinePaymentInterface;
use App\Contracts\PaymentGatewayInterface;
use App\Contracts\RefundablePaymentInterface;

/**
 * Strategy pattern to handle different payment types
 * This ensures we only call methods that the gateway supports
 */
class PaymentStrategyService
{
    /**
     * Process online payment (Stripe, PayPal, etc.)
     */
    public function processOnlinePayment(string $gateway, array $data): array
    {
        $gatewayInstance = PaymentGatewayFactory::create($gateway);

        if (!$gatewayInstance instanceof OnlinePaymentInterface) {
            throw new InvalidArgumentException("Gateway [{$gateway}] does not support online payments");
        }

        // Create payment intent
        $result = $gatewayInstance->createPaymentIntent($data);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'type' => 'online',
            'gateway' => $gateway,
            'payment_intent_id' => $result['payment_intent_id'],
            'client_secret' => $result['client_secret'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'status' => $result['status'],
        ];
    }

    /**
     * Process manual payment (Cash, Bank Transfer)
     */
    public function processManualPayment(string $gateway, array $data): array
    {
        $gatewayInstance = PaymentGatewayFactory::create($gateway);

        if (!$gatewayInstance instanceof ManualPaymentInterface) {
            throw new InvalidArgumentException("Gateway [{$gateway}] does not support manual payments");
        }

        $result = $gatewayInstance->recordPayment($data);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'type' => 'manual',
            'gateway' => $gateway,
            'transaction_id' => $result['transaction_id'],
            'amount' => $result['amount'],
            'currency' => $result['currency'],
            'status' => $result['status'],
            'additional_data' => array_diff_key($result, array_flip([
                'success', 'transaction_id', 'amount', 'currency', 'status'
            ])),
        ];
    }

    /**
     * Verify online payment (Stripe, PayPal, etc.)
     * Uses confirmPayment method from OnlinePaymentInterface
     */
    public function verifyOnlinePayment(string $gateway, string $paymentIntentId): array
    {
        try {
            $gatewayInstance = PaymentGatewayFactory::create($gateway);
            
            // Check if gateway supports online payments
            if (!$gatewayInstance instanceof OnlinePaymentInterface) {
                return [
                    'success' => false,
                    'error' => "Gateway [{$gateway}] does not support online payment verification",
                ];
            }
            
            // Call the gateway's confirmPayment method (which verifies the payment status)
            $result = $gatewayInstance->confirmPayment($paymentIntentId);
            
            return $result;
            
        } catch (InvalidArgumentException $e) {
            Log::error("Gateway error during verification: {$e->getMessage()}");
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error("Online payment verification failed: {$e->getMessage()}", [
                'gateway' => $gateway,
                'payment_intent_id' => $paymentIntentId,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'error' => 'Payment verification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify manual payment
     */
    public function verifyManualPayment(string $gateway, string $transactionId, array $verificationData): array
    {
        $gatewayInstance = PaymentGatewayFactory::create($gateway);

        if (!$gatewayInstance instanceof ManualPaymentInterface) {
            throw new InvalidArgumentException("Gateway [{$gateway}] does not support manual payment verification");
        }

        return $gatewayInstance->verifyPayment($transactionId, $verificationData);
    }

    /**
     * Process refund (only for refundable gateways)
     */
    public function processRefund(string $gateway, string $transactionId, ?float $amount = null): array
    {
        $gatewayInstance = PaymentGatewayFactory::create($gateway);

        if (!$gatewayInstance instanceof RefundablePaymentInterface) {
            throw new InvalidArgumentException("Gateway [{$gateway}] does not support refunds");
        }

        // Check if payment can be refunded
        if (!$gatewayInstance->canRefund($transactionId)) {
            return [
                'success' => false,
                'error' => 'Payment cannot be refunded',
            ];
        }

        return $gatewayInstance->refundPayment($transactionId, $amount);
    }

    /**
     * Get payment status from any gateway
     */
    public function getPaymentStatus(string $gateway, string $transactionId): array
    {
        $gatewayInstance = PaymentGatewayFactory::create($gateway);
        return $gatewayInstance->getPaymentStatus($transactionId);
    }

    /**
     * Check if gateway supports specific capability
     */
    public function supportsRefunds(string $gateway): bool
    {
        return PaymentGatewayFactory::supportsCapability($gateway, RefundablePaymentInterface::class);
    }

    public function supportsOnlinePayment(string $gateway): bool
    {
        return PaymentGatewayFactory::supportsCapability($gateway, OnlinePaymentInterface::class);
    }

    public function supportsManualPayment(string $gateway): bool
    {
        return PaymentGatewayFactory::supportsCapability($gateway, ManualPaymentInterface::class);
    }
}