<?php

namespace App\Http\Controllers\Api;

use App\Models\AllCase;
use App\Models\ChatMessage;
use App\Services\AiChatService;
use App\Services\AiCostCalculatorService;
use App\Services\SubscriptionLimitService;
use App\Services\UsageTrackingService;
use App\Repositories\SubscriptionRepository;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Exception;

class ChatMessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiChatService $aiChatService,
        protected AiCostCalculatorService $costCalculator,
        protected SubscriptionLimitService $limitService,
        protected UsageTrackingService $usageTrackingService,
        protected SubscriptionRepository $subscriptionRepository
    ) {}

    /**
     * Send chat message and get AI response
     * This is the main endpoint for chat functionality
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'message' => 'required|string|min:1',
        ]);

        $user = $request->user();
        $caseId = $validated['all_case_id'];
        $userMessage = $validated['message'];

        try {
            // 1. Verify case ownership
            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();

            if (!$case) {
                return $this->errorResponse('Case not found or access denied', 404);
            }

            // 2. Check subscription limits BEFORE processing
            $limitCheck = $this->limitService->canSendMessage($user->id);
            
            if (!$limitCheck['allowed']) {
                return $this->errorResponse(
                    $limitCheck['reason'] ?? 'Chat limit reached',
                    403,
                    [
                        'upgrade_required' => true,
                        'current_plan' => $limitCheck['current_plan'] ?? null,
                        'upgrade_to' => $limitCheck['upgrade_to'] ?? null,
                        'limit_details' => [
                            'limit' => $limitCheck['limit'] ?? null,
                            'used' => $limitCheck['used'] ?? null,
                            'threshold' => $limitCheck['threshold'] ?? null,
                            'cost_accumulated' => $limitCheck['cost_accumulated'] ?? null,
                        ]
                    ]
                );
            }

            // 3. Get user's subscription and determine AI model
            $subscription = $this->subscriptionRepository->getActiveByUserId($user->id);
            $planTier = $subscription->plan_tier ?? 'free';
            $aiModel = $this->costCalculator->getModelForPlan($planTier);

            // 4. Get conversation history
            $conversationHistory = ChatMessage::where('all_case_id', $caseId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content
                ])
                ->toArray();

            // 5. Build system prompt
            $systemPrompt = $this->aiChatService->buildSystemPrompt([
                'issue_type' => $case->issue_type,
                'location_city' => $case->location_city,
                'location_state' => $case->location_state,
                'location_country' => $case->location_country,
                'situation_description' => $case->situation_description,
            ]);

            // 6. Use database transaction for atomicity
            DB::beginTransaction();

            try {
                // 7. Save user message FIRST
                $userChatMessage = ChatMessage::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $caseId,
                    'user_id' => $user->id,
                    'role' => 'user',
                    'content' => $userMessage,
                    'metadata' => [
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

                // 8. Call AI to generate response
                $aiResponse = $this->aiChatService->generateResponse(
                    $aiModel,
                    $systemPrompt,
                    $conversationHistory,
                    $userMessage
                );

                // 9. Calculate cost
                $cost = $this->costCalculator->calculateCost(
                    $aiModel,
                    $aiResponse['input_tokens'],
                    $aiResponse['output_tokens']
                );

                // 10. Save AI response
                $aiChatMessage = ChatMessage::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $caseId,
                    'user_id' => $user->id,
                    'role' => 'assistant',
                    'content' => $aiResponse['content'],
                    'ai_model_used' => $aiModel,
                    'input_tokens' => $aiResponse['input_tokens'],
                    'output_tokens' => $aiResponse['output_tokens'],
                    'cost' => $cost,
                    'metadata' => [
                        'timestamp' => now()->toISOString(),
                    ],
                ]);

                // 11. Update usage tracking (increment chat count + tokens + cost)
                $today = Carbon::today()->toDateString();
                
                $this->usageTrackingService->incrementUsage($user->id, [
                    'billing_cycle_date' => $today,
                    'messages_used' => 1, // Count only successful exchanges
                    'input_tokens_used' => $aiResponse['input_tokens'],
                    'output_tokens_used' => $aiResponse['output_tokens'],
                    'ai_cost_accumulated' => $cost,
                ]);

                // 12. Check if threshold reached and update
                $threshold = $this->costCalculator->getThreshold($planTier);
                if ($threshold > 0) {
                    $this->usageTrackingService->checkCostThreshold(
                        $user->id,
                        $today,
                        $threshold
                    );
                }

                DB::commit();

                // 13. Get usage warning if approaching limits
                $usageWarning = $this->limitService->getUsageWarning($user->id);

                // 14. Return successful response
                return $this->successResponse([
                    'user_message' => $userChatMessage,
                    'ai_message' => $aiChatMessage,
                    'usage_warning' => $usageWarning,
                    'usage_info' => [
                        'tokens_used' => $aiResponse['input_tokens'] + $aiResponse['output_tokens'],
                        'cost' => $cost,
                        'model' => $aiModel,
                    ]
                ], 'Message sent successfully', 201);

            } catch (Exception $e) {
                DB::rollBack();
                
                Log::error('Chat message processing failed', [
                    'user_id' => $user->id,
                    'case_id' => $caseId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // Note: User message was saved but AI response failed
                // We do NOT increment usage count since the exchange was not successful
                return $this->errorResponse(
                    'Failed to generate AI response. Please try again.',
                    500,
                    ['error_details' => $e->getMessage()]
                );
            }

        } catch (Exception $e) {
            Log::error('Chat endpoint error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'An error occurred while processing your message',
                500
            );
        }
    }

    /**
     * Store a chat message (kept for backward compatibility if needed)
     * This method is NOT used in the new flow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'role' => 'required|in:user,assistant,system',
            'content' => 'required|string',
            'ai_model_used' => 'nullable|string|max:50',
            'input_tokens' => 'nullable|integer',
            'output_tokens' => 'nullable|integer',
            'cost' => 'nullable|numeric',
            'metadata' => 'nullable|array',
        ]);

        try {
            $message = ChatMessage::create([
                'id' => Str::uuid(),
                'all_case_id' => $validated['all_case_id'],
                'user_id' => $request->user()->id,
                'role' => $validated['role'],
                'content' => $validated['content'],
                'ai_model_used' => $validated['ai_model_used'] ?? null,
                'input_tokens' => $validated['input_tokens'] ?? null,
                'output_tokens' => $validated['output_tokens'] ?? null,
                'cost' => $validated['cost'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $message,
            ], 201);

        } catch (Exception $e) {
            Log::error('Failed to store chat message', [
                'error' => $e->getMessage(),
                'case_id' => $validated['all_case_id'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to store message',
            ], 500);
        }
    }

    /**
     * Get all messages for a case
     */
    public function index($caseId)
    {
        try {
            $user = auth('api')->user();
            
            // Find the case
            $case = AllCase::find($caseId);
            
            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case not found',
                ], 404);
            }
            
            // Check ownership
            if ($case->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case does not belong to you',
                ], 403);
            }
            
            // Get messages
            $messages = ChatMessage::where('all_case_id', $caseId)
                ->orderBy('created_at', 'asc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $messages
            ]);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}