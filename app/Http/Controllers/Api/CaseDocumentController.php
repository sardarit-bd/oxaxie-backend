<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseDocument;
use App\Services\FileUploadService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $user = auth()->user();

        // Check authorization
        if ($document->user_id !== $user->id) {
            abort(403, 'Unauthorized access to document');
        }

        // Check if file exists
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
        $user = auth()->user();

        // Check authorization
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

        // Check authorization
        if ($document->user_id !== $user->id) {
            abort(403, 'Unauthorized access to document');
        }

        // Check if file exists
        if (!Storage::disk('private')->exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $file = Storage::disk('private')->get($document->file_path);

        return response($file, 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Length', $document->file_size);
    }

    /**
     * Upload additional documents to existing case.
     */
    public function uploadToCaseAdditional(Request $request, string $caseId)
    {
        $user = $request->user();
        
        // Find case
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
            // Pass the case's issue_type
            $uploadResult = $this->fileUploadService->uploadMultipleFiles(
                $files,
                $case->id,
                $user->id,
                $case->issue_type // Pass the case's issue_type
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