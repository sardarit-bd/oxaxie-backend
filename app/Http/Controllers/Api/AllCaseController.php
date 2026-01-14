<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\AllCase;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\UsageTrackingService;
use App\Http\Requests\Api\AllCaseRequest;
use App\Services\SubscriptionLimitService;

class AllCaseController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SubscriptionLimitService $limitService,
        protected UsageTrackingService $usageTrackingService,
        protected FileUploadService $fileUploadService
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
        
        $documentKeys = [];
        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'document') !== false) {
                $documentKeys[] = $key;
            }
        }
        Log::info('Keys containing "document":', ['keys' => $documentKeys]);

        try {
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
                // Create case
                $case = $user->cases()->create([
                    'issue_type' => $validated['issue_type'],
                    'location_city' => $validated['location_city'] ?? null,
                    'location_state' => $validated['location_state'],
                    'location_country' => $validated['location_country'],
                    'situation_description' => $validated['situation_description'],
                    'status' => 'active'
                ]);

                // Handle file uploads
                $uploadedDocuments = [];
                $uploadErrors = [];
                
                if ($request->hasFile('documents')) {
                    $files = $request->file('documents');
                    $uploadResult = $this->fileUploadService->uploadMultipleFiles(
                        $files,
                        $case->id,
                        $user->id,
                        $validated['issue_type']
                    );
                    
                    $uploadedDocuments = $uploadResult['uploaded'];
                    $uploadErrors = $uploadResult['errors'];
                }

                // Track usage
                $today = Carbon::today()->toDateString();
                $this->usageTrackingService->incrementUsage($user->id, [
                    'billing_cycle_date' => $today,
                    'cases_created' => 1,
                ]);

                DB::commit();

                // Load case with both AI-generated documents and user-uploaded case documents
                $case->load(['documents', 'caseDocuments']);

                $usageSummary = $this->limitService->getUsageSummary($user->id);

                $response = [
                    'case' => $case,
                    'usage_info' => [
                        'cases_created' => $usageSummary['cases']['created'],
                        'cases_limit' => $usageSummary['cases']['limit'],
                        'cases_remaining' => $usageSummary['cases']['remaining'],
                    ]
                ];

                // Include upload info if there were documents
                if (!empty($uploadedDocuments) || !empty($uploadErrors)) {
                    $response['upload_info'] = [
                        'uploaded_count' => count($uploadedDocuments),
                        'errors' => $uploadErrors,
                    ];
                }

                return $this->successResponse(
                    $response,
                    'Case created successfully' . (!empty($uploadErrors) ? ' with some upload errors' : ''),
                    201
                );

            } catch (Exception $e) {
                DB::rollBack();
                
                Log::error('Case creation failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
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
        $case = auth('api')->user()->cases()->with(['documents', 'caseDocuments'])->find($id);

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
            $case->load(['documents', 'caseDocuments']),
            'Case updated successfully',
            200
        );
    }

    /**
     * Get all cases for a user.
     */
    public function userCases()
    {
        return $this->successResponse(
            auth('api')->user()->cases()->with(['caseDocuments'])->latest()->simplePaginate(10),
            'User cases retrieved successfully',
            200
        );
    }

    /**
     * Get user-uploaded documents for a case
     */
    public function getCaseDocuments(string $id)
    {
        $user = auth('api')->user();
        
        $case = $user->cases()->find($id);

        if (!$case) {
            return $this->errorResponse('Case not found', 404);
        }

        // Load only user-uploaded case documents (not AI-generated documents)
        $caseDocuments = $case->caseDocuments()
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse(
            $caseDocuments,
            'Case documents retrieved successfully',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth('api')->user();
        
        $case = $user->cases()->find($id);

        if (!$case) {
            return $this->errorResponse(
                'Case not found',
                404
            );
        }

        // Delete will cascade to both documents and caseDocuments and their files
        $case->delete();

        return $this->successResponse(
            null,
            'Case deleted successfully',
            200
        );
    }

    public function markAsResolved(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            $case = AllCase::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$case) {
                return $this->errorResponse('Case not found or access denied', 404);
            }

            if ($case->status === 'resolved') {
                return $this->errorResponse('Case is already marked as resolved', 400);
            }

            $case->status = 'resolved';
            $case->resolved_at = Carbon::now();
            $case->save();

            Log::info('Case marked as resolved', [
                'case_id' => $id,
                'user_id' => $user->id
            ]);

            return $this->successResponse([
                'case' => $case
            ], 'Case marked as resolved successfully');

        } catch (Exception $e) {
            Log::error('Failed to mark case as resolved', [
                'case_id' => $id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse('Failed to update case status', 500);
        }
    }
}