<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getSubscription()
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $subscription = $user->subscription;
            
            if (!$subscription) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'plan' => 'free'
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'plan' => $subscription->plan_tier ?? 'free',
                    'status' => $subscription->status ?? 'inactive',
                    'expires_at' => $subscription->expires_at ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
