<?php

namespace App\Http\Controllers\Api;

use App\Models\AllCase;
use App\Models\ChatMessage;
use App\Models\Document;
use App\Services\AiChatService;
use App\Services\SubscriptionLimitService;
use App\Services\UsageTrackingService;
use App\Services\AiCostCalculatorService;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use League\CommonMark\CommonMarkConverter;

class DocumentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AiChatService $aiChatService,
        protected SubscriptionLimitService $limitService,
        protected UsageTrackingService $usageTrackingService,
        protected AiCostCalculatorService $costCalculator
    ) {}

    /**
     * Generate a document based on case and chat history
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'all_case_id' => 'required|uuid|exists:all_cases,id',
            'document_type' => 'required|string|in:demand_letter,formal_notice,response_letter,cease_desist',
            'model_name' => 'sometimes|string', // NEW: Allow specifying model
        ]);

        $user = $request->user();
        $caseId = $validated['all_case_id'];
        $documentType = $validated['document_type'];

        try {
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

            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();

            if (!$case) {
                return $this->errorResponse('Case not found or access denied', 404);
            }

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

            DB::beginTransaction();

            try {
                $systemPrompt = $this->buildDocumentPrompt($case, $documentType);
                $userPrompt = $this->buildUserPrompt($chatHistory, $documentType);

                // NEW: Use dynamic AI service
                $aiResponse = $this->aiChatService->generateResponse(
                    $user,
                    $systemPrompt,
                    [],
                    $userPrompt,
                    $validated['model_name'] ?? null
                );

                // Get the model info from response
                $modelUsed = $aiResponse['model_used'];

                // NEW: Calculate cost using model ID from database
                $cost = $this->costCalculator->calculateCostByModelId(
                    $modelUsed['id'],
                    $aiResponse['input_tokens'],
                    $aiResponse['output_tokens'],
                    $user->subscription?->plan_tier ?? 'free'
                );

                $document = Document::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $caseId,
                    'user_id' => $user->id,
                    'name' => $this->getDocumentTitle($documentType),
                    'document_type' => $documentType,
                    'source' => 'generated',
                    'content' => $aiResponse['content'],
                ]);

                $today = Carbon::today()->toDateString();
                $this->usageTrackingService->incrementUsage($user->id, [
                    'billing_cycle_date' => $today,
                    'documents_generated' => 1,
                ]);

                // Track AI usage for cost
                $this->usageTrackingService->trackAiUsage(
                    userId: $user->id,
                    modelId: $modelUsed['id'],
                    inputTokens: $aiResponse['input_tokens'],
                    outputTokens: $aiResponse['output_tokens']
                );

                DB::commit();

                return $this->successResponse([
                    'document' => $document,
                    'tokens_used' => $aiResponse['input_tokens'] + $aiResponse['output_tokens'],
                    'model_used' => $modelUsed,
                    'cost' => $cost,
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
                ['error_details' => config('app.debug') ? $e->getMessage() : null]
            );
        }
    }

    /**
     * Get all documents for a case
     */
    public function index($caseId)
    {
        try {
            $user = auth('api')->user();
            
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
            
            $case = AllCase::where('id', $caseId)
                ->where('user_id', $user->id)
                ->first();

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
            
            $subscription = $user->subscription;
            
            if (!$subscription || $subscription->plan === 'free') {
                return response()->json([
                    'success' => false,
                    'message' => 'Document downloads are not available on the Free plan. Upgrade to Pro to download your documents.',
                    'errors' => [
                        'upgrade_required' => true,
                        'current_plan' => 'free',
                        'upgrade_to' => 'pro'
                    ]
                ], 403);
            }
            
            $document = Document::where('id', $documentId)
                ->where('user_id', $user->id)
                ->first();
            
            if (!$document) {
                return $this->errorResponse('Document not found', 404);
            }
            
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
        $issueType = ucwords(str_replace('_', ' ', $case->issue_type));
        $today = now()->format('F d, Y');
        
        $prompts = [
            'demand_letter' => "You are an expert legal document writer specializing in creating professional demand letters. Your task is to generate a formal, legally sound demand letter based on the case information and conversation history provided.

            **CRITICAL INSTRUCTIONS:**
            1. CAREFULLY ANALYZE the entire conversation history to extract:
            - Specific grievances, disputes, or violations mentioned
            - Dates, amounts, or specific incidents discussed
            - Parties involved and their actions
            - Damages or losses mentioned
            - Previous attempts at resolution
            - Desired outcomes or remedies requested

            2. DO NOT use generic placeholders. Instead:
            - Extract actual facts from the conversation
            - Reference specific events or communications discussed
            - Include concrete details about the dispute
            - Cite specific monetary amounts if discussed
            - Reference actual dates mentioned in the conversation

            3. If critical information is missing from the conversation (like specific amounts or dates), use placeholders like [SPECIFY AMOUNT] or [INSERT DATE], but ONLY when that information truly wasn't discussed.

            **DOCUMENT STRUCTURE:**
            [YOUR NAME]
            [YOUR ADDRESS]
            [YOUR CITY, STATE ZIP]

            {$today}

            [RECIPIENT NAME]
            [RECIPIENT ADDRESS]
            [RECIPIENT CITY, STATE ZIP]

            **Re: Demand for [Specific Action Based on Conversation]**

            Dear [RECIPIENT NAME]:

            **Opening Paragraph:**
            Clearly state the purpose and provide brief context based on the conversation.

            **Statement of Facts:**
            Present a chronological, detailed account of what happened based on the conversation history. Include:
            - Specific dates and events discussed
            - Actions taken or not taken
            - Communications exchanged
            - Violations or breaches identified

            **Legal Basis:**
            - Reference relevant laws, regulations, or contract terms applicable to {$issueType} matters in {$case->location_state}
            - Explain how the facts constitute a violation

            **Demands:**
            Clearly state what you are demanding:
            1. Specific actions required
            2. Monetary compensation (if discussed)
            3. Timeline for compliance (suggest 10-14 days)
            4. Method of response required

            **Consequences:**
            State consequences of non-compliance (legal action, small claims court, regulatory complaints, etc.)

            **Closing:**
            Professional closing with expectation of response.

            Sincerely,

            [YOUR NAME]

            ---
            *Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.*

            **TONE:** Firm, professional, assertive but not aggressive. Legally appropriate and business-like.",

                        'formal_notice' => "You are an expert legal document writer specializing in formal legal notices. Your task is to create a professional formal notice based on the case information and conversation history.

            **CRITICAL INSTRUCTIONS:**
            1. ANALYZE the conversation thoroughly to understand:
            - What violation, breach, or issue needs to be formally noticed
            - What obligations or rights have been violated
            - What specific actions or inactions occurred
            - What the sender wants the recipient to know or do
            - Any deadlines or time-sensitive matters discussed

            2. CREATE A SPECIFIC NOTICE based on actual conversation content:
            - Use real facts and details from the chat
            - Reference specific incidents, dates, or amounts mentioned
            - Address the actual issue discussed, not generic problems
            - Include concrete examples from the conversation

            3. If information is clearly missing, use descriptive placeholders like [SPECIFY THE INCIDENT DATE] but minimize these.

            **DOCUMENT STRUCTURE:**

            **FORMAL LEGAL NOTICE**

            **Date:** {$today}

            **From:**
            [YOUR NAME]
            [YOUR ADDRESS]
            [YOUR CITY, STATE ZIP]

            **To:**
            [RECIPIENT NAME]
            [RECIPIENT ADDRESS]
            [RECIPIENT CITY, STATE ZIP]

            **Subject: Formal Notice Regarding [Specific Issue from Conversation]**

            Dear [RECIPIENT NAME]:

            **Purpose of Notice:**
            Clearly state why this formal notice is being issued, based on the conversation.

            **Statement of Facts and Issues:**
            Detail the specific situation based on the conversation:
            - What happened (chronologically)
            - When it happened (specific dates if discussed)
            - Who is involved
            - What obligations exist (lease terms, laws, agreements)
            - How those obligations were violated

            **Legal Framework:**
            - Reference specific laws, statutes, or regulations applicable to {$issueType} in {$case->location_state}
            - Cite relevant lease clauses or contract terms if discussed
            - Explain the legal rights being asserted

            **Required Actions:**
            Specify exactly what the recipient must do:
            1. [Specific action based on conversation]
            2. [Timeline for compliance - typically 7-14 days]
            3. [Method of compliance or confirmation]

            **Deadline:** [Specific date, typically 14 days from today]

            **Consequences of Non-Compliance:**
            State what will happen if requirements aren't met:
            - Legal proceedings that may be initiated
            - Regulatory complaints that may be filed
            - Additional remedies that may be sought
            - Recovery of attorney fees and costs

            **Documentation:**
            Note that all communications and actions will be documented for potential legal proceedings.

            Sincerely,

            [YOUR NAME]

            ---
            *Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.*

            **TONE:** Formal, serious, official. This is a legal notice that puts the recipient on official notice of their obligations.",

                        'response_letter' => "You are an expert legal document writer specializing in response letters. Your task is to create a professional, strategic response letter based on the case information and conversation history.

            **CRITICAL INSTRUCTIONS:**
            1. UNDERSTAND what is being responded to:
            - Review the conversation to identify what communication, demand, or action is being addressed
            - Identify claims, allegations, or demands made by the other party
            - Note any disputes, disagreements, or clarifications needed
            - Understand the sender's position and desired outcome

            2. CREATE A STRATEGIC RESPONSE that:
            - Directly addresses each point raised (based on conversation)
            - States the sender's position clearly and factually
            - Provides counter-arguments or clarifications where needed
            - Includes supporting facts from the conversation
            - Proposes reasonable solutions or next steps if appropriate
            - Protects the sender's legal rights and interests

            3. Use actual details from the conversation. If responding to a specific letter, demand, or claim, reference it specifically.

            **DOCUMENT STRUCTURE:**

            [YOUR NAME]
            [YOUR ADDRESS]
            [YOUR CITY, STATE ZIP]

            {$today}

            [RECIPIENT NAME]
            [RECIPIENT ADDRESS]
            [RECIPIENT CITY, STATE ZIP]

            **Re: Response to [Specific Communication/Demand/Claim]**

            Dear [RECIPIENT NAME]:

            **Opening:**
            Acknowledge receipt of the communication being responded to. Reference specific dates and subject matter from the conversation.

            **Response to Each Point:**
            Address each claim, demand, or issue raised systematically:

            **Point 1:** [State their claim/demand]
            **Response:** [Your position with supporting facts from conversation]

            **Point 2:** [State their claim/demand]
            **Response:** [Your position with supporting facts from conversation]

            [Continue for all relevant points discussed in conversation]

            **Your Position:**
            Clearly state your overall position on the matter:
            - What you agree or disagree with
            - What facts they have wrong
            - What your rights are under law/contract
            - What you are or are not willing to do

            **Supporting Facts and Legal Basis:**
            - Provide factual support from the conversation
            - Reference applicable laws or lease terms for {$issueType} in {$case->location_state}
            - Cite any evidence or documentation discussed

            **Your Demands/Requests (if applicable):**
            If the sender has counter-demands or requests, state them clearly:
            1. [Specific request based on conversation]
            2. [Expected timeline]
            3. [Proposed resolution]

            **Next Steps:**
            Suggest productive next steps or state what will happen if resolution isn't reached.

            **Closing:**
            Professional closing that keeps the door open for resolution while protecting rights.

            Sincerely,

            [YOUR NAME]

            ---
            *Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.*

            **TONE:** Professional, firm but diplomatic. Assert your position strongly while remaining open to reasonable resolution. Not aggressive, but not weak.",

                        'cease_desist' => "You are an expert legal document writer specializing in cease and desist letters. Your task is to create a strong, legally appropriate cease and desist letter based on the case information and conversation history.

            **CRITICAL INSTRUCTIONS:**
            1. IDENTIFY the specific behavior that must stop:
            - Review conversation carefully for harassment, violations, or improper conduct
            - Note specific incidents with dates/times if discussed
            - Understand the impact on the sender
            - Identify what laws or rights are being violated

            2. CREATE A POWERFUL CEASE AND DESIST that:
            - Specifically describes the offending behavior (not generic)
            - Uses concrete examples from the conversation
            - References actual incidents discussed
            - Cites applicable laws being violated
            - Makes crystal clear what must stop immediately
            - States serious consequences for non-compliance

            3. This is a SERIOUS legal document. Be firm, direct, and authoritative while remaining professional.

            **DOCUMENT STRUCTURE:**

            **[YOUR NAME]**
            **[YOUR ADDRESS]**
            **[YOUR CITY, STATE ZIP]**

            **{$today}**

            **VIA [CERTIFIED MAIL/EMAIL]**

            **[RECIPIENT NAME]**
            **[RECIPIENT ADDRESS]**
            **[RECIPIENT CITY, STATE ZIP]**

            **RE: CEASE AND DESIST – [Specific Unlawful Conduct]**

            Dear [RECIPIENT NAME]:

            **DEMAND TO CEASE AND DESIST**

            This letter constitutes formal notice and demand that you immediately cease and desist from the unlawful conduct described below.

            **DESCRIPTION OF UNLAWFUL CONDUCT:**

            Based on the documented pattern of behavior, you have engaged in the following specific actions:

            [List specific behaviors from conversation, with dates/times if available:]
            1. [Specific incident/behavior discussed]
            2. [Specific incident/behavior discussed]
            3. [Continue with actual examples from conversation]

            **VIOLATIONS OF LAW:**

            Your conduct violates the following laws and legal protections:

            - [Specific state statute for {$issueType} in {$case->location_state}]
            - [Relevant regulations or ordinances]
            - [Lease agreement provisions if applicable]
            - [Constitutional or civil rights if applicable]

            In {$case->location_state}, these violations can result in [civil penalties, criminal charges, damages, etc.].

            **HARM CAUSED:**

            Your actions have caused [specific harms discussed in conversation]:
            - [Emotional distress, financial loss, property damage, etc.]
            - [Impact on daily life, work, or wellbeing]
            - [Any documented damages]

            **IMMEDIATE DEMANDS:**

            You are hereby ordered to IMMEDIATELY CEASE AND DESIST from:

            1. [Specific behavior that must stop]
            2. [Specific behavior that must stop]
            3. [Any contact or communication if discussed]
            4. [Any other specific actions that must stop]

            **You must comply with these demands immediately upon receipt of this letter.**

            **CONSEQUENCES OF NON-COMPLIANCE:**

            Failure to immediately cease the conduct described above will result in:

            1. **Legal Action:** Filing of a lawsuit seeking injunctive relief and monetary damages
            2. **Criminal Complaints:** Report to law enforcement if conduct is criminal
            3. **Regulatory Complaints:** Report to [housing authority, labor board, etc.]
            4. **Damages:** Pursuit of all available damages including compensatory, statutory, and punitive damages
            5. **Attorney Fees:** Recovery of all legal fees and costs as permitted by law

            **REQUIRED WRITTEN CONFIRMATION:**

            Within seven (7) calendar days of receiving this letter, you must provide written confirmation that you will comply with these demands and cease all unlawful conduct.

            **PRESERVATION OF RIGHTS:**

            This letter does not waive any rights or remedies, all of which are expressly reserved. All future violations will be documented and used as evidence in legal proceedings.

            **FINAL WARNING:**

            This is your final opportunity to resolve this matter without litigation. Take this demand seriously.

            Sincerely,

            **[YOUR NAME]**

            ---
            *Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.*

            **TONE:** Serious, firm, authoritative, and uncompromising. This is a final warning before legal action. Direct and powerful."
        ];

        return $prompts[$documentType] ?? $prompts['demand_letter'];
    }

    /**
     * Build user prompt from chat history
     */
    protected function buildUserPrompt($chatHistory, $documentType): string
    {
        $conversationContext = "**COMPLETE CONVERSATION HISTORY:**\n\n";
        $conversationContext .= "Review this entire conversation carefully to extract all relevant facts, details, dates, amounts, and context needed for the document.\n\n";
        
        foreach ($chatHistory as $index => $msg) {
            $role = $msg['role'] === 'user' ? 'USER' : 'ASSISTANT';
            $conversationContext .= "**Message #{$index} - {$role}:**\n{$msg['content']}\n\n";
        }
        
        $documentName = $this->getDocumentTitle($documentType);
        
        $conversationContext .= "\n---\n\n";
        $conversationContext .= "**YOUR TASK:**\n\n";
        $conversationContext .= "Generate a professional {$documentName} that:\n\n";
        $conversationContext .= "1. **Uses Actual Facts**: Extract and use specific details, incidents, dates, amounts, and circumstances discussed in the conversation above\n\n";
        $conversationContext .= "2. **Addresses Real Issues**: Focus on the actual problems, disputes, or violations mentioned in the chat\n\n";
        $conversationContext .= "3. **Is Specific**: Avoid generic language. Reference concrete examples from the conversation\n\n";
        $conversationContext .= "4. **Is Contextual**: Understand the full story and context from the conversation flow\n\n";
        $conversationContext .= "5. **Is Action-Oriented**: Based on what the user discussed, make clear what they want or what should happen\n\n";
        $conversationContext .= "6. **Is Ready to Use**: Make it as complete as possible with the information available\n\n";
        
        if ($documentType === 'response_letter') {
            $conversationContext .= "\n**SPECIAL INSTRUCTION FOR RESPONSE LETTER:**\n";
            $conversationContext .= "If the conversation discusses a specific letter, demand, or claim received, structure your response to address each point raised. If no specific communication is being responded to, respond to the overall situation discussed.\n\n";
        }
        
        if ($documentType === 'cease_desist') {
            $conversationContext .= "\n**SPECIAL INSTRUCTION FOR CEASE AND DESIST:**\n";
            $conversationContext .= "Identify specific behaviors, actions, or conduct discussed in the conversation that need to stop. Be very specific about what conduct is unacceptable and must cease immediately.\n\n";
        }
        
        $conversationContext .= "Generate the complete {$documentName} now, incorporating all relevant information from the conversation above.";
        
        return $conversationContext;
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


    public function downloadDocument(Request $request, $documentId)
    {
        $format = $request->query('format', 'pdf');
        
        $document = Document::findOrFail($documentId);
        
        if ($document->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $filename = str_replace(' ', '_', $document->name);
        
        if ($format === 'pdf') {
            return $this->generatePDF($document, $filename);
        } elseif ($format === 'docx') {
            return $this->generateDOCX($document, $filename);
        }
        
        abort(400, 'Invalid format');
    }

    private function generatePDF($document, $filename)
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
    
        $html = $this->markdownToHtml($document->content);
        
        $styledHtml = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='utf-8'>
                <style>
                    @page {
                        margin: 1in;
                    }
                    body {
                        font-family: 'DejaVu Sans', Arial, sans-serif;
                        line-height: 1.8;
                        color: #000000;
                        font-size: 12pt;
                    }
                    h1 {
                        font-size: 18pt;
                        font-weight: bold;
                        margin-top: 0;
                        margin-bottom: 20px;
                        text-align: center;
                    }
                    h2 {
                        font-size: 14pt;
                        font-weight: bold;
                        margin-top: 25px;
                        margin-bottom: 15px;
                    }
                    h3 {
                        font-size: 12pt;
                        font-weight: bold;
                        margin-top: 20px;
                        margin-bottom: 10px;
                    }
                    p {
                        margin-bottom: 12px;
                        text-align: justify;
                    }
                    ul, ol {
                        margin-left: 30px;
                        margin-bottom: 12px;
                    }
                    li {
                        margin-bottom: 8px;
                    }
                    strong {
                        font-weight: bold;
                    }
                    em {
                        font-style: italic;
                    }
                    blockquote {
                        margin-left: 20px;
                        margin-right: 20px;
                        padding-left: 15px;
                        border-left: 3px solid #cccccc;
                        font-style: italic;
                    }
                    hr {
                        border: none;
                        border-top: 1px solid #000000;
                        margin: 20px 0;
                    }
                </style>
            </head>
            <body>
                {$html}
            </body>
            </html>
        ";
        
        $dompdf->loadHtml($styledHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Track download
        $document->increment('download_count');
        $document->update(['last_downloaded_at' => now()]);
        
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"');
    }

    private function generateDOCX($document, $filename)
    {
        $phpWord = new PhpWord();
        
        $properties = $phpWord->getDocInfo();
        $properties->setCreator('Advocate Legal Assistant');
        $properties->setTitle($document->name);

        $section = $phpWord->addSection([
            'marginLeft' => 1440, 
            'marginRight' => 1440,
            'marginTop' => 1440,
            'marginBottom' => 1440,
        ]);

        $html = $this->markdownToHtml($document->content);
        
        Html::addHtml($section, $html, false, false);

        $tempFile = tempnam(sys_get_temp_dir(), 'word_');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        $document->increment('download_count');
        $document->update(['last_downloaded_at' => now()]);
        
        return response()->download($tempFile, $filename . '.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ])->deleteFileAfterSend(true);
    }

    private function markdownToHtml($markdown)
    {
        if (empty($markdown)) {
            return '<p>No content available</p>';
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        
        return $converter->convert($markdown)->getContent();
    }
}