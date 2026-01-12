<?php

namespace App\Http\Controllers\Api;

use Exception;
use Carbon\Carbon;
use App\Models\AllCase;
use App\Models\ChatMessage;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\AiChatService;
use App\Models\ResponseFeedback;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\UsageTrackingService;
use App\Services\AiCostCalculatorService;
use App\Services\SubscriptionLimitService;
use App\Repositories\SubscriptionRepository;

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
        Log::info('=== Chat Send Request ===');
        Log::info('Request data:', $request->all());
        
        $validated = $request->validate([
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'message' => 'required|string|min:1',
            'messages' => 'sometimes|array',
            'system_prompt' => 'sometimes|string',
            'feedback_id' => 'sometimes|uuid|exists:response_feedback,id',
            'feedback_documents' => 'sometimes|array',
        ]);

        $user = $request->user();
        $caseId = $validated['all_case_id'];
        $userMessage = $validated['message'];

        try {
            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();

            if (!$case) {
                return $this->errorResponse('Case not found or access denied', 404);
            }

            $limitCheck = $this->limitService->canSendMessage($user->id);
            
            if (!$limitCheck['allowed']) {
                return $this->errorResponse(
                    $limitCheck['reason'] ?? 'Chat limit reached',
                    403,
                    [
                        'upgrade_required' => true,
                        'current_plan' => $limitCheck['current_plan'] ?? null,
                        'upgrade_to' => $limitCheck['upgrade_to'] ?? null,
                        'can_purchase_credits' => $limitCheck['can_purchase_credits'] ?? false,
                        'credit_options' => $limitCheck['credit_options'] ?? null,
                        'limit_details' => [
                            'limit' => $limitCheck['limit'] ?? null,
                            'used' => $limitCheck['used'] ?? null,
                            'threshold' => $limitCheck['threshold'] ?? null,
                            'cost_accumulated' => $limitCheck['cost_accumulated'] ?? null,
                            'credits_available' => $limitCheck['credits_available'] ?? null,
                        ]
                    ]
                );
            }

            $subscription = $this->subscriptionRepository->getActiveByUserId($user->id);
            $planTier = $subscription->plan_tier ?? 'free';
            $aiModel = $this->costCalculator->getModelForPlan($planTier);

            if (isset($validated['messages'])) {
                Log::info('Using messages from frontend with images');
                $conversationHistory = $validated['messages'];
            } else {
                Log::info('Building messages from database');
                $conversationHistory = ChatMessage::where('all_case_id', $caseId)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(fn($msg) => [
                        'role' => $msg->role,
                        'content' => $msg->content
                    ])
                    ->toArray();
            }

            if (isset($validated['system_prompt'])) {
                $systemPrompt = $validated['system_prompt'];
            } else {
                $systemPrompt = $this->aiChatService->buildSystemPrompt([
                    'issue_type' => $case->issue_type,
                    'location_city' => $case->location_city,
                    'location_state' => $case->location_state,
                    'location_country' => $case->location_country,
                    'situation_description' => $case->situation_description,
                ]);
            }

            DB::beginTransaction();

            try {
                // Save user message
                $userChatMessage = ChatMessage::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $caseId,
                    'user_id' => $user->id,
                    'role' => 'user',
                    'content' => $userMessage,
                    'metadata' => [
                        'timestamp' => now()->toISOString(),
                        'feedback_id' => $validated['feedback_id'] ?? null, 
                        'type' => isset($validated['feedback_id']) ? 'response_feedback' : 'normal', 
                    ],
                ]);

                $aiResponse = $this->aiChatService->generateResponseWithMessages(
                    $aiModel,
                    $systemPrompt,
                    $conversationHistory
                );

                $cost = $this->costCalculator->calculateCost(
                    $aiModel,
                    $aiResponse['input_tokens'],
                    $aiResponse['output_tokens']
                );

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
                        'feedback_id' => $validated['feedback_id'] ?? null, 
                        'type' => isset($validated['feedback_id']) ? 'feedback_analysis' : 'normal', 
                    ],
                ]);

                if (isset($validated['feedback_id'])) {
                    ResponseFeedback::where('id', $validated['feedback_id'])
                        ->update(['sent_to_chat' => true]);
                }

                $usageResult = $this->usageTrackingService->trackAiUsage(
                    userId: $user->id,
                    model: $aiModel,
                    inputTokens: $aiResponse['input_tokens'],
                    outputTokens: $aiResponse['output_tokens']
                );

                Log::info('Usage tracked successfully', [
                    'user_id' => $user->id,
                    'cost_added' => $usageResult['cost_added'],
                    'total_cost' => $usageResult['total_cost'],
                    'threshold_reached' => $usageResult['threshold_reached'],
                    'needs_credits' => $usageResult['needs_credits'] ?? false,
                ]);

                DB::commit();

                $usageWarning = $this->limitService->getUsageWarning($user->id);

                $responseData = [
                    'user_message' => $userChatMessage,
                    'ai_message' => $aiChatMessage,
                    'usage_warning' => $usageWarning,
                    'usage_info' => [
                        'tokens_used' => $aiResponse['input_tokens'] + $aiResponse['output_tokens'],
                        'cost' => $cost,
                        'total_cost' => $usageResult['total_cost'],
                        'model' => $aiModel,
                    ]
                ];

                if ($usageResult['threshold_reached']) {
                    if ($usageResult['needs_credits']) {
                        $responseData['critical_warning'] = [
                            'type' => 'credits_needed',
                            'message' => 'You have reached your usage limit. Purchase additional credits to continue.',
                            'can_purchase_credits' => true,
                            'credit_options' => [5.00, 10.00, 20.00],
                            'credits_available' => $usageResult['available_credits'],
                        ];
                    } else {
                        $responseData['critical_warning'] = [
                            'type' => 'upgrade_needed',
                            'message' => 'You have reached your $5 threshold. Upgrade to Pro Plus for higher limits.',
                            'upgrade_to' => 'pro_plus',
                        ];
                    }
                }

                return $this->successResponse(
                    $responseData,
                    'Message sent successfully',
                    201
                );

            } catch (Exception $e) {
                DB::rollBack();
                
                Log::error('Chat message processing failed', [
                    'user_id' => $user->id,
                    'case_id' => $caseId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->errorResponse(
                    'Failed to generate AI response. Please try again.',
                    500,
                    ['error_details' => config('app.debug') ? $e->getMessage() : null]
                );
            }

        } catch (Exception $e) {
            Log::error('Chat endpoint error', [
                'user_id' => $user->id ?? 'unknown',
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
            
            $case = AllCase::find($caseId);
            
            if (!$case) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case not found',
                ], 404);
            }
            
            if ($case->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Case does not belong to you',
                ], 403);
            }
            
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