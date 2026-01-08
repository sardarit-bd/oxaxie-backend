<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseDocument extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'all_case_id',
        'response_feedback_id',
        'user_id',
        'original_name',
        'stored_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type',
    ];


    public function case(): BelongsTo
    {
        return $this->belongsTo(AllCase::class, 'all_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function responseFeedback(): BelongsTo
    {
        return $this->belongsTo(ResponseFeedback::class, 'response_feedback_id');
    }

    public function isResponseDocument(): bool
    {
        return !is_null($this->response_feedback_id);
    }
}