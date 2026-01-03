<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\AllCaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\SubscriptionController;

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

    // Subscription route
    Route::post('/subscriptions/store-or-update', [SubscriptionController::class, 'storeOrUpdate']);
});

Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook']);

// Protected routes example
Route::middleware('auth:api')->group(function () {
    Route::get('users', function () {
        return response()->json(['message' => 'This is a protected route']);
    });
});
