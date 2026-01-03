<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Store or update a user's subscription
     */
    public function storeOrUpdate(Request $request)
    {
        $user = $request->user();

        // Validate input
        $validator = Validator::make($request->all(), [
            'plan_tier' => 'required|in:free,pro,pro_plus',
            'status' => 'required|in:active,cancelled,expired,past_due',
            'monthly_price' => 'required|numeric|min:0',
            'current_period_start' => 'required|date',
            'current_period_end' => 'required|date|after_or_equal:current_period_start',
            'stripe_subscription_id' => 'required|string',
            'stripe_customer_id' => 'nullable|string',
            'cancelled_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Check if subscription exists by user or stripe ID
        $subscription = Subscription::where('user_id', $user->id)
            ->orWhere('stripe_subscription_id', $data['stripe_subscription_id'])
            ->first();

        if ($subscription) {
            $subscription->update($data);
            $message = 'Subscription updated successfully';
        } else {
            $subscription = Subscription::create(array_merge($data, [
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
            ]));
            $message = 'Subscription created successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $subscription,
        ]);
    }
}
