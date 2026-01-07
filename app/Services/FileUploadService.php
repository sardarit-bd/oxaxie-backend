<?php

namespace App\Services;

use App\Models\CaseDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    // Allowed MIME types
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
    ];

    // Max file size: 10MB
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    // Max files per case
    private const MAX_FILES_PER_CASE = 3;

    /**
     * Validate uploaded file.
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];


        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $errors[] = "File '{$file->getClientOriginalName()}' exceeds maximum size of 10MB";
        }


        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            $errors[] = "File '{$file->getClientOriginalName()}' has an unsupported file type";
        }

        if (!$file->isValid()) {
            $errors[] = "File '{$file->getClientOriginalName()}' upload failed";
        }

        return $errors;
    }

    /**
     * Upload file and create document record.
     */
    public function uploadFile(UploadedFile $file, string $caseId, string $userId, string $issueType): CaseDocument
    {
 
        $errors = $this->validateFile($file);
        if (!empty($errors)) {
            throw new Exception(implode(', ', $errors));
        }

        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;
 
        $filePath = "case-documents/{$caseId}/{$storedName}";
        Storage::disk('private')->put($filePath, file_get_contents($file->getRealPath()));


        $document = CaseDocument::create([
            'all_case_id' => $caseId,
            'user_id' => $userId,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'file_path' => $filePath,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $issueType,
        ]);

        return $document;
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultipleFiles(array $files, string $caseId, string $userId, string $issueType): array
    {
        $uploadedDocuments = [];
        $errors = [];

        $existingCount = CaseDocument::where('all_case_id', $caseId)->count();
        if ($existingCount + count($files) > self::MAX_FILES_PER_CASE) {
            throw new Exception("Maximum of " . self::MAX_FILES_PER_CASE . " files allowed per case");
        }

        foreach ($files as $file) {
            try {
                $document = $this->uploadFile($file, $caseId, $userId, $issueType);
                $uploadedDocuments[] = $document;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'uploaded' => $uploadedDocuments,
            'errors' => $errors,
        ];
    }

    /**
     * Delete document file.
     */
    public function deleteFile(CaseDocument $document): bool
    {
        if (Storage::disk('private')->exists($document->file_path)) {
            return Storage::disk('private')->delete($document->file_path);
        }

        return false;
    }

    /**
     * Get allowed MIME types.
     */
    public static function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    /**
     * Get max file size.
     */
    public static function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * Get max files per case.
     */
    public static function getMaxFilesPerCase(): int
    {
        return self::MAX_FILES_PER_CASE;
    }
}