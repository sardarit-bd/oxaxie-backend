<?php

namespace App\Services;

use App\Models\ResponseFeedback;
use App\Models\AllCase;
use App\Repositories\ResponseFeedbackRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ResponseFeedbackService
{
    public function __construct(
        protected ResponseFeedbackRepository $repository,
        protected AiChatService $aiChatService
    ) {}

    /**
     * Create new response feedback
     */
    public function createFeedback(string $userId, string $caseId, array $data): ResponseFeedback
    {
        try {

            $feedbackData = [
                'user_id' => $userId,
                'all_case_id' => $caseId,
                'response_type' => $data['response_type'],
                'response_description' => $data['response_description'],
                'response_date' => $data['response_date'],
                'action_taken_date' => $data['action_taken_date'] ?? null,
                'status' => 'active',
            ];

            $feedback = $this->repository->create($feedbackData);

    
            if ($feedback->action_taken_date && $feedback->response_date) {
                $feedback->calculateDaysToResponse();
            }

            return $feedback;

        } catch (Exception $e) {
            Log::error('Failed to create response feedback', [
                'user_id' => $userId,
                'case_id' => $caseId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get all feedback for a case
     */
    public function getFeedbackForCase(string $userId, string $caseId): Collection
    {
        return $this->repository->findByCaseId($caseId, $userId);
    }

    /**
     * Get single feedback
     */
    public function getFeedback(string $userId, string $feedbackId): ResponseFeedback
    {
        $feedback = $this->repository->findByIdAndUser($feedbackId, $userId);

        if (!$feedback) {
            throw new Exception('Response feedback not found');
        }

        return $feedback;
    }

    /**
     * Update feedback
     */
    public function updateFeedback(string $userId, string $feedbackId, array $data): ResponseFeedback
    {
        $feedback = $this->getFeedback($userId, $feedbackId);

        $this->repository->update($feedback, $data);


        if (isset($data['action_taken_date']) || isset($data['response_date'])) {
            $feedback->calculateDaysToResponse();
        }

        return $feedback->fresh();
    }

    /**
     * Delete feedback
     */
    public function deleteFeedback(string $userId, string $feedbackId): bool
    {
        $feedback = $this->getFeedback($userId, $feedbackId);
        return $this->repository->delete($feedback);
    }

    /**
     * Analyze feedback with AI
     */
    public function analyzeWithAI(string $userId, string $feedbackId): ResponseFeedback
    {
        $feedback = $this->getFeedback($userId, $feedbackId);
        $case = $feedback->case;

        if (!$case) {
            throw new Exception('Case not found for this feedback');
        }

        try {
 
            $systemPrompt = $this->buildAnalysisPrompt($feedback, $case);
            $userPrompt = $this->buildAnalysisRequest($feedback, $case);

 
            $model = config('services.gemini.model', 'gemini-1.5-flash');
            $aiResponse = $this->aiChatService->generateResponse(
                $model,
                $systemPrompt,
                [],
                $userPrompt
            );

            $aiData = $this->parseAiAnalysis($aiResponse['content'], $feedback);

            $this->repository->markAsAnalyzed($feedback, $aiData);

            return $feedback->fresh();

        } catch (Exception $e) {
            Log::error('AI analysis failed', [
                'feedback_id' => $feedbackId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get case statistics
     */
    public function getCaseStatistics(string $userId, string $caseId): array
    {
        return $this->repository->getCaseStatistics($caseId, $userId);
    }

    /**
     * Build AI analysis system prompt
     */
    protected function buildAnalysisPrompt(ResponseFeedback $feedback, AllCase $case): string
    {
        return "You are a legal strategy advisor analyzing an opposing party's response to legal action.

        **Your Role:**
        - Analyze the response objectively
        - Assess compliance level and implications
        - Suggest strategic next steps
        - Evaluate urgency and recommend deadlines
        - Provide escalation options

        **Case Context:**
        - Issue Type: {$case->issue_type}
        - Location: {$case->location_city}, {$case->location_state}
        - Original Situation: {$case->situation_description}

        **Response Details:**
        - Response Type: {$feedback->response_type}
        - Response Date: {$feedback->response_date->format('Y-m-d')}
        - Days to Respond: {$feedback->days_to_response} days
        - Description: {$feedback->response_description}

        **Your Task:**
        Provide a structured analysis in JSON format with these fields:
        - analysis: Detailed analysis of the response
        - next_steps: Specific actions the user should take
        - escalation_options: Array of possible escalation paths
        - urgency_level: One of: low, medium, high, critical
        - recommended_deadline: Date by which next action should be taken (YYYY-MM-DD format)

        Be specific, actionable, and consider legal timelines.";
    }

    /**
     * Build user analysis request
     */
    protected function buildAnalysisRequest(ResponseFeedback $feedback, AllCase $case): string
    {
        return "Analyze this response and provide strategic guidance in JSON format.";
    }

    /**
     * Parse AI analysis into structured data
     */
    protected function parseAiAnalysis(string $aiContent, ResponseFeedback $feedback): array
    {
        $jsonMatch = [];
        preg_match('/\{[\s\S]*\}/', $aiContent, $jsonMatch);

        if (!empty($jsonMatch[0])) {
            try {
                $data = json_decode($jsonMatch[0], true);

                return [
                    'analysis' => $data['analysis'] ?? $aiContent,
                    'next_steps' => $data['next_steps'] ?? null,
                    'escalation_options' => $data['escalation_options'] ?? [],
                    'urgency_level' => $data['urgency_level'] ?? 'medium',
                    'recommended_deadline' => $data['recommended_deadline'] ?? 
                        Carbon::now()->addDays(14)->toDateString(),
                ];
            } catch (Exception $e) {
                Log::warning('Failed to parse AI JSON, using raw content', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'analysis' => $aiContent,
            'next_steps' => null,
            'escalation_options' => [],
            'urgency_level' => $this->calculateUrgencyLevel($feedback),
            'recommended_deadline' => Carbon::now()->addDays(14)->toDateString(),
        ];
    }

    /**
     * Calculate urgency level based on response type
     */
    protected function calculateUrgencyLevel(ResponseFeedback $feedback): string
    {
        return match($feedback->response_type) {
            'refused' => 'high',
            'no_response' => 'high',
            'counter_offer' => 'medium',
            'partial_compliance' => 'medium',
            'complied' => 'low',
            default => 'medium',
        };
    }
}