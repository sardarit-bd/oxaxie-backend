<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;
use App\Models\CaseDocument;
use Illuminate\Http\Request;
use App\Models\ResponseFeedback;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseDocumentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileUploadService $fileUploadService
    ) {}

    /**
     * Download a case document.
     */
    public function download(CaseDocument $document): StreamedResponse
    {
        $user = auth('api')->user();

 
        if ($document->user_id !== $user->id) {
            abort(403, 'Unauthorized access to document');
        }


        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk('private')->download(
            $document->file_path,
            $document->original_name
        );
    }

    /**
     * Delete a case document.
     */
    public function destroy(CaseDocument $document)
    {
        $user = auth('api')->user();

        if ($document->user_id !== $user->id) {
            return $this->errorResponse('Unauthorized', 403);
        }

        try {
            $this->fileUploadService->deleteFile($document);
            $document->delete();

            return $this->successResponse(
                null,
                'Document deleted successfully',
                200
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete document',
                500
            );
        }
    }


    /**
     * Get document file content (for images)
     */
    public function getDocumentContent(CaseDocument $document)
    {
        $user = auth('api')->user();


        if ($document->user_id !== $user->id) {
            abort(403, 'Unauthorized access to document');
        }


        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $file = Storage::disk('private')->get($document->file_path);

        return response($file, 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Length', $document->file_size);
    }

    /**
     * Upload documents to response feedback
     * POST /api/feedback/{feedbackId}/documents
     */
    public function uploadToFeedback(Request $request, string $feedbackId)
    {
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:5',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(
                'Validation error',
                422,
                $validator->errors()
            );
        }

        try {
            $user = $request->user();
            
            $feedback = ResponseFeedback::where('id', $feedbackId)
                ->where('user_id', $user->id)
                ->first();

            if (!$feedback) {
                return $this->errorResponse('Response feedback not found', 404);
            }

            $uploadedDocuments = [];

            foreach ($request->file('documents') as $file) {
                $originalName = $file->getClientOriginalName();
                $storedName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('response_feedback_documents', $storedName, 'public');

                $document = CaseDocument::create([
                    'id' => Str::uuid(),
                    'all_case_id' => $feedback->all_case_id,
                    'response_feedback_id' => $feedbackId,
                    'user_id' => $user->id,
                    'original_name' => $originalName,
                    'stored_name' => $storedName,
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'document_type' => 'other',
                ]);

                $uploadedDocuments[] = $document;
            }

            Log::info('Documents uploaded to response feedback', [
                'feedback_id' => $feedbackId,
                'user_id' => $user->id,
                'count' => count($uploadedDocuments)
            ]);

            return $this->successResponse(
                $uploadedDocuments,
                'Documents uploaded successfully',
                201
            );

        } catch (Exception $e) {
            Log::error('Document upload failed', [
                'feedback_id' => $feedbackId,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to upload documents',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Upload additional documents to existing case.
     */
    public function uploadToCaseAdditional(Request $request, string $caseId)
    {
        $user = $request->user();

        $case = $user->cases()->find($caseId);
        if (!$case) {
            return $this->errorResponse('Case not found', 404);
        }

        $maxFileSize = FileUploadService::getMaxFileSize() / 1024;
        
        $request->validate([
            'documents' => 'required|array|max:20',
            'documents.*' => [
                'file',
                'max:' . $maxFileSize,
            ],
        ]);

        try {
            $files = $request->file('documents');

            $uploadResult = $this->fileUploadService->uploadMultipleFiles(
                $files,
                $case->id,
                $user->id,
                $case->issue_type
            );

            return $this->successResponse(
                [
                    'uploaded_documents' => $uploadResult['uploaded'],
                    'errors' => $uploadResult['errors'],
                    'uploaded_count' => count($uploadResult['uploaded']),
                ],
                'Documents uploaded successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                400
            );
        }
    }
}