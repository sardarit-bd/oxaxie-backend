<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\AllCaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UsageTrackingController;
use App\Http\Controllers\Api\CreditPurchaseController;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('me', 'me');
});


Route::middleware('auth:api')->group(function () {
    // case routes
    Route::apiResource('/case', AllCaseController::class);
    Route::get('/case/user/all-cases', [AllCaseController::class, 'userCases']);

    // chat routes
    Route::post('/chat-messages', [ChatMessageController::class, 'store']);
    Route::get('/cases/{caseId}/messages', [ChatMessageController::class, 'index']);

    // payment gateway routes
    Route::get('/payments/gateways', [PaymentController::class, 'getAvailableGateways']);
    
    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/initialize', [PaymentController::class, 'initializePayment']);
        Route::post('/{paymentId}/verify', [PaymentController::class, 'verifyPayment']);
        Route::post('/{paymentId}/mark-received', [PaymentController::class, 'markAsReceived']);
        Route::get('/{paymentId}', [PaymentController::class, 'show']);
        Route::post('/{paymentId}/refund', [PaymentController::class, 'refund']);
    });

    // ============================================
    // SUBSCRIPTION ROUTES
    // One record per user - updates on plan changes
    // ============================================
    Route::prefix('subscriptions')->group(function () {
        // Store or update subscription (main endpoint)
        Route::post('/store-or-update', [SubscriptionController::class, 'storeOrUpdate']);
        
        // View subscription
        Route::get('/', [SubscriptionController::class, 'show']);
        Route::get('/active', [SubscriptionController::class, 'active']);
        Route::get('/has-active', [SubscriptionController::class, 'hasActive']);
        
        // Update subscription
        Route::put('/', [SubscriptionController::class, 'update']);
        Route::patch('/', [SubscriptionController::class, 'update']);
        
        // Cancel subscription
        Route::post('/cancel', [SubscriptionController::class, 'cancel']);
        
        // Delete subscription
        Route::delete('/', [SubscriptionController::class, 'destroy']);
    });

    // ============================================
    // CREDIT PURCHASE ROUTES
    // Multiple records per user - immutable transactions
    // ============================================
    Route::prefix('credit-purchases')->group(function () {
        // Create new purchase (always creates new record)
        Route::post('/', [CreditPurchaseController::class, 'store']);
        
        // Update only status (amounts are immutable)
        Route::patch('/{id}/status', [CreditPurchaseController::class, 'updateStatus']);
        
        // View purchases
        Route::get('/', [CreditPurchaseController::class, 'index']);
        Route::get('/{id}', [CreditPurchaseController::class, 'show']);
        Route::get('/history/all', [CreditPurchaseController::class, 'history']);
        Route::get('/credits/available', [CreditPurchaseController::class, 'availableCredits']);
    });

    // ============================================
    // USAGE TRACKING ROUTES
    // One record per user per billing cycle - updates throughout cycle
    // ============================================
    Route::prefix('usage')->group(function () {
        // Record/update usage (upsert pattern)
        Route::post('/record', [UsageTrackingController::class, 'recordUsage']);
        
        // Increment usage (for real-time tracking)
        Route::post('/increment', [UsageTrackingController::class, 'incrementUsage']);
        
        // View usage
        Route::get('/current', [UsageTrackingController::class, 'getCurrentUsage']);
        Route::get('/history', [UsageTrackingController::class, 'getUsageHistory']);
        Route::get('/summary', [UsageTrackingController::class, 'getUsageSummary']);
        Route::get('/{id}', [UsageTrackingController::class, 'show']);
        
        // Threshold management
        Route::post('/check-threshold', [UsageTrackingController::class, 'checkCostThreshold']);
    });
});

Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook']);

// Protected routes example
Route::middleware('auth:api')->group(function () {
    Route::get('users', function () {
        return response()->json(['message' => 'This is a protected route']);
    });
});
