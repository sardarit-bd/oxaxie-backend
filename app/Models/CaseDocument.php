<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CaseDocument extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'case_documents';

    protected $fillable = [
        'all_case_id',
        'user_id',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = ['file_size_formatted', 'file_url'];

    /**
     * Get the case that owns the document.
     */
    public function case()
    {
        return $this->belongsTo(AllCase::class, 'all_case_id');
    }

    /**
     * Get the user that owns the document.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted file size.
     */
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        
        return $bytes . ' B';
    }

    /**
     * Get the file URL.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (Storage::disk('private')->exists($this->file_path)) {
            return route('case.document.download', ['document' => $this->id]);
        }
        
        return null;
    }

    /**
     * Delete the file from storage when model is deleted.
     */
    protected static function boot()
    {
        parent::boot();

        static::deleted(function ($document) {
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
        });
    }
}