<?php

namespace App\Http\Controllers;

use App\Http\Resources\RegisterUserResource;
use App\Models\User;
use App\Services\UserSetupService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserSetupService $userSetupService
    ) {}

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        try {
            // Create user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Setup user account (subscription, credits, usage tracking)
            $setupData = $this->userSetupService->setupNewUser($user);

            // Generate auth token
            $token = auth()->login($user);

            // Return response with setup data
            return response()->json([
                'success' => true,
                'message' => 'Successfully registered',
                'data' => [
                    'user' => new RegisterUserResource($user),
                    'authorization' => [
                        'token' => $token,
                        'type' => 'bearer',
                    ],
                    'setup' => [
                        'subscription' => $setupData['subscription'],
                        'initial_credits' => $setupData['credit_purchase']->credits_added,
                        'usage_tracking_initialized' => true,
                    ]
                ]
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse(
                'Registration failed: ' . $e->getMessage(),
                500
            );
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->authResponse(
            new RegisterUserResource(auth()->user()),
            $token,
            'Login successful'
        );
    }

    public function logout()
    {
        auth()->logout();
        
        return $this->successResponse(
            null,
            'Successfully logged out'
        );
    }

    public function me()
    {
        return response()->json(auth()->user());
    }

    public function refresh()
    {
        $token = auth()->refresh();
        
        return $this->authResponse(
            new RegisterUserResource(auth()->user()),
            $token,
            'Token refreshed successfully'
        );
    }
}