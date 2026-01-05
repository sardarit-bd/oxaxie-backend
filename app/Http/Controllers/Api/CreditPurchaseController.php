<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\CreditPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

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
            'amount' => 'required|numeric|min:0.01',
            'credits_added' => 'required|numeric|min:0',
            'expires_at' => 'required|date|after:now',
            'stripe_payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $creditPurchase = $this->creditPurchaseService->createPurchase(
                $request->user()->id,
                $validator->validated()
            );

            return $this->successResponse(
                $creditPurchase,
                'Credit purchase created successfully',
                201
            );
        } catch (Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'already been processed') ? 409 : 
                         (str_contains($e->getMessage(), 'not found') ? 404 : 400);
            
            return $this->errorResponse($e->getMessage(), $statusCode);
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