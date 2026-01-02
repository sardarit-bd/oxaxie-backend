<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentRequest;
use App\Http\Requests\RefundPaymentRequest;
use App\Http\Requests\VerifyPaymentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * Get available payment gateways
     */
    public function getAvailableGateways(): JsonResponse
    {
        $gateways = $this->paymentService->getAvailableGateways();

        $gatewaysWithCapabilities = [];
        foreach ($gateways as $gateway) {
            $gatewaysWithCapabilities[$gateway] = $this->paymentService->getGatewayCapabilities($gateway);
        }

        return response()->json([
            'success' => true,
            'gateways' => $gatewaysWithCapabilities,
        ]);
    }

    /**
     * Initialize payment (works for both online and manual)
     */
    public function initializePayment(CreatePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        $result = $this->paymentService->initializePayment($data);

        if ($result['success']) {
            $responseData = [
                'payment_id' => $result['payment']->id,
                'transaction_id' => $result['payment']->transaction_id,
                'amount' => $result['payment']->amount,
                'currency' => $result['payment']->currency,
                'status' => $result['payment']->status,
                'type' => $result['type'],
            ];

            // Add type-specific data
            if ($result['type'] === 'online') {
                $responseData['client_secret'] = $result['client_secret'];
                $responseData['payment_intent_id'] = $result['payment_intent_id'];
            } elseif ($result['type'] === 'manual') {
                $responseData['additional_data'] = $result['additional_data'] ?? [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment initialized successfully',
                'data' => $responseData,
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to initialize payment',
            'error' => $result['error'] ?? 'Unknown error',
        ], 400);
    }

    /**
     * Verify manual payment (for cash, bank transfer, etc.)
     */
    public function verifyPayment(VerifyPaymentRequest $request, int $paymentId): JsonResponse
    {
        $verificationData = $request->validated();

        $result = $this->paymentService->verifyManualPayment($paymentId, $verificationData);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => [
                    'payment' => $result['payment'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to verify payment',
            'error' => $result['error'] ?? 'Unknown error',
        ], 400);
    }

    /**
     * Mark payment as received (for manual payments)
     */
    public function markAsReceived(int $paymentId): JsonResponse
    {
        $result = $this->paymentService->markPaymentAsReceived($paymentId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment marked as received',
                'data' => [
                    'payment' => $result['payment'],
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to mark payment as received',
            'error' => $result['error'] ?? 'Unknown error',
        ], 400);
    }

    /**
     * Get payment details
     */
    public function show(int $paymentId): JsonResponse
    {
        $payment = $this->paymentService->getPaymentDetails($paymentId);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }

        // Check if user owns this payment
        if ($payment->user_id !== auth('api')->id() && !auth('admin')->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    /**
     * Get user payments
     */
    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getUserPayments($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    /**
     * Refund payment
     */
    public function refund(RefundPaymentRequest $request, int $paymentId): JsonResponse
    {
        $data = $request->validated();
        $amount = $data['amount'] ?? null;

        $result = $this->paymentService->refundPayment($paymentId, $amount);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => $result,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to refund payment',
            'error' => $result['error'] ?? 'Unknown error',
        ], 400);
    }
}