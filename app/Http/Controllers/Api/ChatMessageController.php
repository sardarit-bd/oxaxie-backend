<?php

namespace App\Http\Controllers\Api;

use App\Models\ChatMessage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ChatMessageController extends Controller
{
    /**
     * Store a new chat message
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

        } catch (\Exception $e) {
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
    public function index(Request $request, $caseId)
    {
        $case = $request->user()->allCases()->findOrFail($caseId);

        if ($case) {
            $messages = ChatMessage::where('all_case_id', $caseId)
            ->orderBy('created_at', 'asc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        }
    }
}
