<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\AllCaseController;
use App\Http\Controllers\Api\ChatMessageController;

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
});

// Protected routes example
Route::middleware('auth:api')->group(function () {
    Route::get('users', function () {
        return response()->json(['message' => 'This is a protected route']);
    });
});
