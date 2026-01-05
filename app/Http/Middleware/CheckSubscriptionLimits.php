<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionLimitService;
use App\Traits\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionLimits
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionLimitService $limitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->errorResponse('Unauthenticated', 401);
        }

        $checkResult = match ($feature) {
            'chat' => $this->limitService->canSendMessage($user->id),
            'case' => $this->limitService->canCreateCase($user->id),
            'document' => $this->limitService->canGenerateDocument($user->id),
            default => ['allowed' => false, 'reason' => 'Invalid feature'],
        };

        if (!$checkResult['allowed']) {
            return $this->errorResponse(
                $checkResult['reason'] ?? 'Feature access denied',
                403,
                [
                    'upgrade_required' => true,
                    'current_plan' => $checkResult['current_plan'] ?? null,
                    'upgrade_to' => $checkResult['upgrade_to'] ?? null,
                    'limit_details' => [
                        'limit' => $checkResult['limit'] ?? null,
                        'used' => $checkResult['used'] ?? null,
                    ]
                ]
            );
        }

        // Add usage info to request for controller access
        $request->merge([
            'subscription_check' => $checkResult
        ]);

        return $next($request);
    }
}