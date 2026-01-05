<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AllCaseRequest;
use App\Services\SubscriptionLimitService;
use App\Services\UsageTrackingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class AllCaseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionLimitService $limitService,
        protected UsageTrackingService $usageTrackingService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AllCaseRequest $request)
    {
        $user = $request->user();

        try {
            // 1. Check if user can create case
            $limitCheck = $this->limitService->canCreateCase($user->id);
            
            if (!$limitCheck['allowed']) {
                return $this->errorResponse(
                    $limitCheck['reason'] ?? 'Case creation limit reached',
                    403,
                    [
                        'upgrade_required' => true,
                        'current_plan' => $limitCheck['current_plan'] ?? null,
                        'upgrade_to' => $limitCheck['upgrade_to'] ?? null,
                        'limit_details' => [
                            'limit' => $limitCheck['limit'] ?? null,
                            'used' => $limitCheck['used'] ?? null,
                        ]
                    ]
                );
            }

            $validated = $request->validated();

            DB::beginTransaction();

            try {
                // 2. Create the case
                $case = $user->cases()->create([
                    'issue_type' => $validated['issue_type'],
                    'location_city' => $validated['location_city'] ?? null,
                    'location_state' => $validated['location_state'],
                    'location_country' => $validated['location_country'],
                    'situation_description' => $validated['situation_description'],
                    'status' => 'active'
                ]);

                // 3. Increment case count in usage tracking
                $today = Carbon::today()->toDateString();
                
                $this->usageTrackingService->incrementUsage($user->id, [
                    'billing_cycle_date' => $today,
                    'cases_created' => 1,
                ]);

                DB::commit();

                // 4. Get remaining cases for user info
                $usageSummary = $this->limitService->getUsageSummary($user->id);

                return $this->successResponse(
                    [
                        'case' => $case,
                        'usage_info' => [
                            'cases_created' => $usageSummary['cases']['created'],
                            'cases_limit' => $usageSummary['cases']['limit'],
                            'cases_remaining' => $usageSummary['cases']['remaining'],
                        ]
                    ],
                    'Case created successfully',
                    201
                );

            } catch (Exception $e) {
                DB::rollBack();
                
                Log::error('Case creation failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return $this->errorResponse(
                    'Failed to create case. Please try again.',
                    500
                );
            }

        } catch (Exception $e) {
            Log::error('Case store endpoint error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while creating the case',
                500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $case = auth()->user()->cases()->find($id);

        if ($case) {
            return $this->successResponse(
                $case,
                'Case found successfully',
                200
            );
        }

        return $this->errorResponse(
            'Case not found',
            404
        );
    }

    /**
     * Get all cases for a user.
     */
    public function userCases()
    {
        return $this->successResponse(
            auth()->user()->cases()->simplePaginate(10),
            'User cases retrieved successfully',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        
        $case = $user->cases()->find($id);

        if (!$case) {
            return $this->errorResponse(
                'Case not found',
                404
            );
        }

        $validated = $request->validate([
            'issue_type' => 'sometimes|string',
            'location_city' => 'sometimes|nullable|string',
            'location_state' => 'sometimes|string',
            'location_country' => 'sometimes|string',
            'situation_description' => 'sometimes|string',
            'status' => 'sometimes|in:active,closed,archived',
        ]);

        $case->update($validated);

        return $this->successResponse(
            $case,
            'Case updated successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        
        $case = $user->cases()->find($id);

        if (!$case) {
            return $this->errorResponse(
                'Case not found',
                404
            );
        }

        $case->delete();

        return $this->successResponse(
            null,
            'Case deleted successfully',
            200
        );
    }
}