<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AllCaseController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\CaseOutcomeController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\ContactInfoController;
use App\Http\Controllers\Api\CaseDocumentController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UsageTrackingController;
use App\Http\Controllers\Api\ContactMessageController;
use App\Http\Controllers\Api\CreditPurchaseController;
use App\Http\Controllers\Api\ResponseFeedbackController;

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('refresh', 'refresh');

    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
});


Route::middleware('auth:api')->group(function () {
    // user routes
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('/user/subscription', [UserController::class, 'getSubscription']);
    Route::post('/subscription/downgrade', [SubscriptionController::class, 'downgrade']);

    // case routes
    Route::apiResource('/case', AllCaseController::class);
    Route::get('/case/user/all-cases', [AllCaseController::class, 'userCases']);

    Route::get('/case/document/{document}/download', [CaseDocumentController::class, 'download'])
        ->name('case.document.download');
    Route::get('/case/document/{document}/content', [CaseDocumentController::class, 'getDocumentContent']);
    Route::delete('/case/document/{document}', [CaseDocumentController::class, 'destroy']);
    Route::post('/case/{caseId}/documents', [CaseDocumentController::class, 'uploadToCaseAdditional']);
    Route::get('/case/{id}/case-documents', [AllCaseController::class, 'getCaseDocuments']);



    // chat routes
    Route::post('/chat/send', [ChatMessageController::class, 'sendMessage']);
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

    
    // subscription routes
    Route::prefix('subscriptions')->group(function () {

        Route::post('/store-or-update', [SubscriptionController::class, 'storeOrUpdate']);

        Route::get('/', [SubscriptionController::class, 'show']);
        Route::get('/active', [SubscriptionController::class, 'active']);
        Route::get('/has-active', [SubscriptionController::class, 'hasActive']);

        Route::put('/', [SubscriptionController::class, 'update']);
        Route::patch('/', [SubscriptionController::class, 'update']);
 
        Route::post('/cancel', [SubscriptionController::class, 'cancel']);

        Route::delete('/', [SubscriptionController::class, 'destroy']);
    });


    // credit purchase routes
    Route::prefix('credit-purchases')->group(function () {
        Route::post('/', [CreditPurchaseController::class, 'store']);

        Route::patch('/{id}/status', [CreditPurchaseController::class, 'updateStatus']);

        Route::get('/', [CreditPurchaseController::class, 'index']);
        Route::get('/{id}', [CreditPurchaseController::class, 'show']);
        Route::get('/history/all', [CreditPurchaseController::class, 'history']);
        Route::get('/credits/available', [CreditPurchaseController::class, 'availableCredits']);
    });


    // usage tracking routes
    Route::prefix('usage')->group(function () {
        Route::post('/record', [UsageTrackingController::class, 'recordUsage']);

        Route::post('/increment', [UsageTrackingController::class, 'incrementUsage']);

        Route::get('/current', [UsageTrackingController::class, 'getCurrentUsage']);
        Route::get('/history', [UsageTrackingController::class, 'getUsageHistory']);
        Route::get('/summary', [UsageTrackingController::class, 'getUsageSummary']);
        Route::get('/{id}', [UsageTrackingController::class, 'show']);

        Route::post('/check-threshold', [UsageTrackingController::class, 'checkCostThreshold']);

    });


    // response feedback routes
    Route::prefix('feedback')->group(function () {
        Route::post('/cases/{caseId}/feedback', [ResponseFeedbackController::class, 'store']);
        Route::get('/cases/{caseId}/feedback', [ResponseFeedbackController::class, 'index']);
        Route::get('/cases/{caseId}/feedback/statistics', [ResponseFeedbackController::class, 'statistics']);
        Route::get('/cases/{caseId}/pending-feedback', [ResponseFeedbackController::class, 'getPendingFeedback']);
        
        Route::get('/{id}', [ResponseFeedbackController::class, 'show']);
        Route::put('/{id}', [ResponseFeedbackController::class, 'update']);
        Route::delete('/{id}', [ResponseFeedbackController::class, 'destroy']);
        
        Route::post('/{id}/analyze', [ResponseFeedbackController::class, 'analyzeWithAI']);

        Route::post('/{feedbackId}/documents', [CaseDocumentController::class, 'uploadToFeedback']);
    });

    // Document routes
    Route::post('/documents/generate', [DocumentController::class, 'generate']);
    Route::get('/cases/{caseId}/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{documentId}', [DocumentController::class, 'show']);
    Route::get('/documents/{documentId}/download', [DocumentController::class, 'download']);
    Route::delete('/documents/{documentId}', [DocumentController::class, 'destroy']);

    // Case outcome routes
    Route::patch('/case/{id}/mark-resolved', [AllCaseController::class, 'markAsResolved']);

    Route::prefix('case')->group(function () {
        Route::post('/{caseId}/outcome', [CaseOutcomeController::class, 'store']);
        Route::get('/{caseId}/outcome', [CaseOutcomeController::class, 'show']);
    });

    Route::prefix('outcomes')->group(function () {
        Route::get('/', [CaseOutcomeController::class, 'index']);
        Route::put('/{outcomeId}', [CaseOutcomeController::class, 'update']);
        Route::delete('/{outcomeId}', [CaseOutcomeController::class, 'destroy']);
    });
});

Route::get('/outcomes/testimonials', [CaseOutcomeController::class, 'testimonials']);

Route::get('/outcomes/statistics', [CaseOutcomeController::class, 'statistics']);

// contact 
Route::post('/contact', [ContactMessageController::class, 'store']);

// contact info
Route::get('/contact-info', [ContactInfoController::class, 'index']);

Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripeWebhook']);