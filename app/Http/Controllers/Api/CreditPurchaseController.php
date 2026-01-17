<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\CreditPurchaseService;
use Illuminate\Support\Facades\Validator;

class CreditPurchaseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CreditPurchaseService $creditPurchaseService
    ) {}

    /**
     * Store a new credit purchase
     * Credit purchases are financial transactions and should be immutable
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|in:5,10,20',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Invalid credit amount. Choose $5, $10, or $20.',
                422,
                $validator->errors()
            );
        }

        try {
            $user = $request->user();
            $amount = $request->amount;
            
            // Get user's subscription
            $subscription = $user->subscription;
            
            if (!$subscription) {
                return $this->errorResponse('No active subscription found', 404);
            }
            
            if ($subscription->plan_tier !== 'pro_plus') {
                return $this->errorResponse(
                    'Credit purchases are only available for Pro Plus members.',
                    403
                );
            }

            // Initialize Stripe Checkout
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $checkoutSession = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => "AI Usage Credits - $$amount",
                            'description' => "Add $$amount credits to your Pro Plus plan",
                        ],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => env('FRONTEND_URL', 'http://localhost:3000') . '/credits/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('FRONTEND_URL', 'http://localhost:3000') . '/dashboard',
                'client_reference_id' => $user->id,
                'metadata' => [
                    'type' => 'credit_purchase',
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'credits_amount' => $amount,
                    'expires_at' => $subscription->current_period_end,
                ],
            ]);

            Log::info('Stripe checkout session created', [
                'session_id' => $checkoutSession->id,
                'user_id' => $user->id,
                'amount' => $amount,
            ]);

            return $this->successResponse([
                'checkout_url' => $checkoutSession->url,
                'session_id' => $checkoutSession->id,
            ], 'Stripe checkout session created', 201);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);
            
            return $this->errorResponse(
                'Payment system error. Please try again.',
                500
            );
        } catch (Exception $e) {
            Log::error('Credit purchase error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);
            
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update credit purchase status (ONLY for status changes)
     * Don't allow changing amounts or credits - those are immutable
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $creditPurchase = $this->creditPurchaseService->updateStatus(
                $request->user()->id,
                $id,
                $request->status
            );

            return $this->successResponse(
                $creditPurchase,
                'Credit purchase status updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Get all credit purchases for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $creditPurchases = $this->creditPurchaseService->getUserPurchases(
                $request->user()->id
            );

            return $this->successResponse(
                $creditPurchases,
                'Credit purchases retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get credit purchase history with summary
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $data = $this->creditPurchaseService->getPurchaseHistory(
                $request->user()->id
            );

            return $this->successResponse(
                $data,
                'Credit purchase history retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get available (non-expired, completed) credits
     */
    public function availableCredits(Request $request): JsonResponse
    {
        try {
            $availableCredits = $this->creditPurchaseService->getAvailableCredits(
                $request->user()->id
            );

            return $this->successResponse(
                ['available_credits' => $availableCredits],
                'Available credits retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get specific credit purchase
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $creditPurchase = $this->creditPurchaseService->getPurchaseById(
                $request->user()->id,
                $id
            );

            return $this->successResponse(
                $creditPurchase,
                'Credit purchase retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

}