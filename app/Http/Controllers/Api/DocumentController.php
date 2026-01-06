<?php

namespace App\Http\Controllers\Api;

use App\Models\AllCase;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Services\AiChatService;
use App\Services\SubscriptionLimitService;
use App\Services\UsageTrackingService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class DocumentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiChatService $aiChatService,
        protected SubscriptionLimitService $limitService,
        protected UsageTrackingService $usageTrackingService
    ) {}

    /**
     * Generate a document based on case and chat history
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'document_type' => 'required|string|in:demand_letter,formal_notice,response_letter,cease_desist',
        ]);

        $user = $request->user();
        $caseId = $validated['all_case_id'];
        $documentType = $validated['document_type'];

        try {
            // 1. Check document generation limit
            $limitCheck = $this->limitService->canGenerateDocument($user->id);
            
            if (!$limitCheck['allowed']) {
                return $this->errorResponse(
                    $limitCheck['reason'] ?? 'Document generation limit reached',
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

            // 2. Verify case ownership
            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();

            if (!$case) {
                return $this->errorResponse('Case not found or access denied', 404);
            }

            // 3. Get conversation history
            $chatHistory = ChatMessage::where('all_case_id', $caseId)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($msg) => [
                    'role' => $msg->role,
                    'content' => $msg->content
                ])
                ->toArray();

            if (empty($chatHistory)) {
                return $this->errorResponse(
                    'No conversation history found. Please chat with the AI first to generate context for the document.',
                    400
                );
            }

            // 4. Start database transaction
            DB::beginTransaction();

            try {
                // 5. Build document generation prompt
                $systemPrompt = $this->buildDocumentPrompt($case, $documentType);
                $userPrompt = $this->buildUserPrompt($chatHistory, $documentType);

                // 6. Generate document using AI
                $model = config('services.gemini.model', 'gemini-1.5-flash');
                
                $aiResponse = $this->aiChatService->generateResponse(
                    $model,
                    $systemPrompt,
                    [],
                    $userPrompt
                );

                // 7. Save document to database
                $document = Document::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $caseId,
                    'user_id' => $user->id,
                    'name' => $this->getDocumentTitle($documentType),
                    'document_type' => $documentType,
                    'source' => 'generated',
                    'content' => $aiResponse['content'],
                ]);

                // 8. Update usage tracking
                $today = Carbon::today()->toDateString();
                $this->usageTrackingService->incrementUsage($user->id, [
                    'billing_cycle_date' => $today,
                    'documents_generated' => 1,
                ]);

                DB::commit();

                return $this->successResponse([
                    'document' => $document,
                    'tokens_used' => $aiResponse['input_tokens'] + $aiResponse['output_tokens'],
                ], 'Document generated successfully', 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Document generation failed', [
                'user_id' => $user->id,
                'case_id' => $caseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to generate document. Please try again.',
                500,
                ['error_details' => $e->getMessage()]
            );
        }
    }

    /**
     * Get all documents for a case
     */
    // public function index($caseId)
    // {
    //     try {
    //         $user = auth('api')->user();
            
    //         // Verify case ownership
    //         $case = AllCase::where('id', $caseId)
    //             ->where('user_id', $user->id)
    //             ->first();
            
    //         if (!$case) {
    //             return $this->errorResponse('Case not found or access denied', 404);
    //         }
            
    //         // Get documents
    //         $documents = Document::where('all_case_id', $caseId)
    //             ->orderBy('created_at', 'desc')
    //             ->get()
    //             ->map(function ($doc) {
    //                 return [
    //                     'id' => $doc->id,
    //                     'name' => $doc->name,
    //                     'document_type' => $doc->document_type,
    //                     'source' => $doc->source,
    //                     'content' => $doc->content,
    //                     'download_count' => $doc->download_count,
    //                     'last_downloaded_at' => $doc->last_downloaded_at,
    //                     'created_at' => $doc->created_at,
    //                 ];
    //             });
            
    //         return $this->successResponse(
    //             $documents,
    //             'Documents fetched successfully'
    //         );
            
    //     } catch (Exception $e) {
    //         Log::error('Failed to fetch documents', [
    //             'case_id' => $caseId,
    //             'error' => $e->getMessage()
    //         ]);
            
    //         return $this->errorResponse('Failed to fetch documents', 500);
    //     }
    // }
    
    public function index($caseId)
    {
        try {
            // Get authenticated user
            $user = auth('api')->user();
            
            // Debug logging
            Log::info('Fetching documents', [
                'case_id' => $caseId,
                'user_id' => $user ? $user->id : 'null',
                'auth_guard' => 'api',
                'has_user' => !is_null($user)
            ]);
            
            if (!$user) {
                Log::error('No authenticated user found');
                return $this->errorResponse('Authentication required', 401);
            }
            
            // Verify case ownership
            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();
            
            // More detailed logging
            if (!$case) {
                $caseExists = AllCase::where('id', $caseId)->first();
                
                Log::error('Case access denied', [
                    'case_id' => $caseId,
                    'user_id' => $user->id,
                    'case_exists' => !is_null($caseExists),
                    'case_owner' => $caseExists ? $caseExists->user_id : null,
                    'user_cases_count' => AllCase::where('user_id', $user->id)->count()
                ]);
                
                return $this->errorResponse('Case not found or access denied', 404, [
                    'debug' => [
                        'case_exists' => !is_null($caseExists),
                        'requesting_user' => $user->id,
                        'case_owner' => $caseExists ? $caseExists->user_id : null
                    ]
                ]);
            }
            
            // Get documents
            $documents = Document::where('all_case_id', $caseId)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'name' => $doc->name,
                        'document_type' => $doc->document_type,
                        'source' => $doc->source,
                        'content' => $doc->content,
                        'download_count' => $doc->download_count,
                        'last_downloaded_at' => $doc->last_downloaded_at,
                        'created_at' => $doc->created_at,
                    ];
                });
            
            Log::info('Documents fetched successfully', [
                'case_id' => $caseId,
                'document_count' => $documents->count()
            ]);
            
            return $this->successResponse(
                $documents,
                'Documents fetched successfully'
            );
            
        } catch (Exception $e) {
            Log::error('Failed to fetch documents', [
                'case_id' => $caseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Failed to fetch documents', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }
    /**
     * Get a single document
     */
    public function show($documentId)
    {
        try {
            $user = auth('api')->user();
            
            $document = Document::where('id', $documentId)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$document) {
                return $this->errorResponse('Document not found', 404);
            }
            
            return $this->successResponse($document, 'Document fetched successfully');
            
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch document', 500);
        }
    }

    /**
     * Delete a document
     */
    public function destroy($documentId)
    {
        try {
            $user = auth('api')->user();
            
            $document = Document::where('id', $documentId)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$document) {
                return $this->errorResponse('Document not found', 404);
            }
            
            $document->delete();
            
            return $this->successResponse(null, 'Document deleted successfully');
            
        } catch (Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return $this->errorResponse('Failed to delete document', 500);
        }
    }

    /**
     * Download/track document download
     */
    public function download($documentId)
    {
        try {
            $user = auth('api')->user();
            
            $document = Document::where('id', $documentId)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$document) {
                return $this->errorResponse('Document not found', 404);
            }
            
            // Increment download count
            $document->incrementDownload();
            
            return $this->successResponse([
                'document' => $document,
                'content' => $document->content,
            ], 'Document ready for download');
            
        } catch (Exception $e) {
            return $this->errorResponse('Failed to download document', 500);
        }
    }

    /**
     * Build system prompt for document generation
     */
    protected function buildDocumentPrompt($case, $documentType): string
    {
        $prompts = [
            'demand_letter' => "You are a professional legal document writer. Generate a formal demand letter based on the case information and conversation history provided. 

            The letter should:
            - Be professional, clear, and legally appropriate
            - Include proper formatting with clear sections
            - Be specific about demands and grievances
            - Set reasonable deadlines for response/action
            - Reference relevant laws or regulations when applicable
            - Use formal legal language but remain understandable

            Format the document with:
            - Proper heading with sender/recipient information placeholders
            - Date
            - Clear subject line
            - Body with numbered or bulleted points where appropriate
            - Professional closing
            - Signature line",
                        
                        'formal_notice' => "You are a professional legal document writer. Generate a formal legal notice based on the case information and conversation history.

            The notice should:
            - Clearly state the issue and relevant facts
            - Reference applicable laws, contracts, or agreements
            - Specify required actions and deadlines
            - Be firm but professional in tone
            - Include consequences of non-compliance

            Use proper legal notice formatting with clear sections and formal language.",
                        
                        'response_letter' => "You are a professional legal document writer. Generate a formal response letter based on the case information and conversation history.

            The letter should:
            - Address all points raised in prior communications
            - State your position clearly and firmly
            - Provide supporting facts and legal basis
            - Be diplomatic yet assertive
            - Propose next steps or solutions where appropriate

            Maintain professional business letter formatting throughout.",
                        
                        'cease_desist' => "You are a professional legal document writer. Generate a cease and desist letter based on the case information and conversation history.

            The letter should:
            - Clearly identify the offending behavior or action
            - Demand immediate cessation of the behavior
            - Reference relevant laws being violated
            - Warn of specific legal consequences if behavior continues
            - Set a clear deadline for compliance
            - Be firm and serious in tone while remaining professional

            Use standard cease and desist letter formatting with clear sections.",
        ];

        $basePrompt = $prompts[$documentType] ?? $prompts['demand_letter'];

        $issueType = ucwords(str_replace('_', ' ', $case->issue_type));

        return $basePrompt . "\n\n**Case Context:**\n" .
            "Issue Type: {$issueType}\n" .
            "Location: {$case->location_city}, {$case->location_state}, {$case->location_country}\n" .
            "Situation: {$case->situation_description}\n\n" .
            "**Important Instructions:**\n" .
            "- Use [YOUR NAME], [YOUR ADDRESS], [YOUR CITY, STATE ZIP] for sender information\n" .
            "- Use [RECIPIENT NAME], [RECIPIENT ADDRESS], [RECIPIENT CITY, STATE ZIP] for recipient\n" .
            "- Use today's date: " . now()->format('F d, Y') . "\n" .
            "- Base the content on the conversation history that will be provided\n" .
            "- Make it ready to use with minimal edits needed\n" .
            "- Include a note at the bottom: 'Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.'";
    }

    /**
     * Build user prompt from chat history
     */
    protected function buildUserPrompt($chatHistory, $documentType): string
    {
        $conversationSummary = "**Conversation History:**\n\n";

        $recentHistory = array_slice($chatHistory, -10);
        
        foreach ($recentHistory as $msg) {
            $role = $msg['role'] === 'user' ? 'User' : 'Assistant';
            $conversationSummary .= "**{$role}:** {$msg['content']}\n\n";
        }
        
        $documentName = $this->getDocumentTitle($documentType);
        
        $conversationSummary .= "\n**Task:**\n" .
            "Based on the above conversation and case context, generate a professional {$documentName} " .
            "that addresses all the key points, issues, and concerns discussed. " .
            "The document should be formal, legally appropriate, and ready to use.";
        
        return $conversationSummary;
    }

    /**
     * Get document title by type
     */
    protected function getDocumentTitle($documentType): string
    {
        return match($documentType) {
            'demand_letter' => 'Demand Letter',
            'formal_notice' => 'Formal Notice',
            'response_letter' => 'Response Letter',
            'cease_desist' => 'Cease and Desist Letter',
            default => 'Legal Document',
        };
    }
}