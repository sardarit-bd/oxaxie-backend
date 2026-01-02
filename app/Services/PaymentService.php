<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Services\PaymentStrategyService;
use App\Factories\PaymentGatewayFactory;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $paymentRepository,
        protected PaymentStrategyService $strategyService
    ) {
    }

    /**
     * Initialize payment based on gateway type
     */
    public function initializePayment(array $data): array
    {
        $gateway = $data['gateway'] ?? 'stripe';

        // Determine payment type based on gateway capabilities
        if ($this->strategyService->supportsOnlinePayment($gateway)) {
            return $this->initializeOnlinePayment($data);
        } elseif ($this->strategyService->supportsManualPayment($gateway)) {
            return $this->initializeManualPayment($data);
        }

        return [
            'success' => false,
            'error' => 'Gateway does not support any known payment type',
        ];
    }

    /**
     * Initialize online payment (Stripe, PayPal, etc.)
     */
    protected function initializeOnlinePayment(array $data): array
    {
        $gateway = $data['gateway'] ?? 'stripe';

        $paymentData = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ];

        $result = $this->strategyService->processOnlinePayment($gateway, $paymentData);

        if ($result['success']) {
            // Create payment record in database
            $payment = $this->paymentRepository->create([
                'user_id' => $data['user_id'],
                'payment_gateway' => $gateway,
                'transaction_id' => Str::uuid()->toString(),
                'payment_intent_id' => $result['payment_intent_id'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'status' => 'pending',
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'type' => 'online',
                ]),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'client_secret' => $result['client_secret'],
                'payment_intent_id' => $result['payment_intent_id'],
                'type' => 'online',
            ];
        }

        return $result;
    }

    /**
     * Initialize manual payment (Cash, Bank Transfer)
     */
    protected function initializeManualPayment(array $data): array
    {
        $gateway = $data['gateway'];

        $paymentData = [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ];

        $result = $this->strategyService->processManualPayment($gateway, $paymentData);

        if ($result['success']) {
            // Create payment record in database
            $payment = $this->paymentRepository->create([
                'user_id' => $data['user_id'],
                'payment_gateway' => $gateway,
                'transaction_id' => $result['transaction_id'],
                'payment_intent_id' => null,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'usd',
                'status' => $result['status'],
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'type' => 'manual',
                    'additional_data' => $result['additional_data'] ?? [],
                ]),
            ]);

            return [
                'success' => true,
                'payment' => $payment,
                'type' => 'manual',
                'additional_data' => $result['additional_data'] ?? [],
            ];
        }

        return $result;
    }

    /**
     * Verify manual payment
     */
    public function verifyManualPayment(int $paymentId, array $verificationData): array
    {
        $payment = $this->paymentRepository->findById($paymentId);

        if (!$payment) {
            return [
                'success' => false,
                'error' => 'Payment not found',
            ];
        }

        $result = $this->strategyService->verifyManualPayment(
            $payment->payment_gateway,
            $payment->transaction_id,
            $verificationData
        );

        if ($result['success']) {
            $this->paymentRepository->update($payment, [
                'status' => $result['status'] ?? 'verified',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'verified_at' => $result['verified_at'] ?? now()->toISOString(),
                    'verified_by' => $result['verified_by'] ?? null,
                ]),
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh(),
            ];
        }

        return $result;
    }

    /**
     * Mark manual payment as received
     */
    public function markPaymentAsReceived(int $paymentId): array
    {
        $payment = $this->paymentRepository->findById($paymentId);

        if (!$payment) {
            return [
                'success' => false,
                'error' => 'Payment not found',
            ];
        }

        $gateway = PaymentGatewayFactory::create($payment->payment_gateway);

        if ($gateway instanceof \App\Contracts\ManualPaymentInterface) {
            $result = $gateway->markAsReceived($payment->transaction_id);

            if ($result['success']) {
                $this->paymentRepository->update($payment, [
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]);

                return [
                    'success' => true,
                    'payment' => $payment->fresh(),
                ];
            }

            return $result;
        }

        return [
            'success' => false,
            'error' => 'Gateway does not support manual payment marking',
        ];
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(string $paymentIntentId, string $status): ?object
    {
        $payment = $this->paymentRepository->findByPaymentIntentId($paymentIntentId);

        if (!$payment) {
            $payment = $this->paymentRepository->findByTransactionId($paymentIntentId);
        }

        if ($payment) {
            $this->paymentRepository->update($payment, [
                'status' => $status,
                'paid_at' => $status === 'succeeded' ? now() : null,
            ]);

            return $payment->fresh();
        }

        return null;
    }

    /**
     * Refund a payment
     */
    public function refundPayment(int $paymentId, ?float $amount = null): array
    {
        $payment = $this->paymentRepository->findById($paymentId);

        if (!$payment) {
            return [
                'success' => false,
                'error' => 'Payment not found',
            ];
        }

        if (!$payment->isSuccessful()) {
            return [
                'success' => false,
                'error' => 'Only successful payments can be refunded',
            ];
        }

        if (!$this->strategyService->supportsRefunds($payment->payment_gateway)) {
            return [
                'success' => false,
                'error' => 'This payment method does not support refunds',
            ];
        }

        $transactionId = $payment->payment_intent_id ?? $payment->transaction_id;
        $result = $this->strategyService->processRefund(
            $payment->payment_gateway,
            $transactionId,
            $amount
        );

        if ($result['success']) {
            $this->paymentRepository->update($payment, [
                'status' => 'refunded',
            ]);

            return [
                'success' => true,
                'payment' => $payment->fresh(),
                'refund' => $result,
            ];
        }

        return $result;
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(int $paymentId): ?object
    {
        return $this->paymentRepository->findById($paymentId);
    }

    /**
     * Get user payments
     */
    public function getUserPayments(int $userId)
    {
        return $this->paymentRepository->getByUserId($userId);
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): array
    {
        return PaymentGatewayFactory::getAvailableGateways();
    }

    /**
     * Get gateway capabilities
     */
    public function getGatewayCapabilities(string $gateway): array
    {
        return PaymentGatewayFactory::getGatewayCapabilities($gateway);
    }
}