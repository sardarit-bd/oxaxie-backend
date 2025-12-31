<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LegalTemplate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'template_name',
        'document_type',
        'issue_type',
        'jurisdiction_state',
        'jurisdiction_country',
        'template_content',
        'required_fields',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'required_fields' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Helper methods
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function matchesJurisdiction(string $state, string $country): bool
    {
        return ($this->jurisdiction_state === $state || $this->jurisdiction_state === null)
            && $this->jurisdiction_country === $country;
    }
}
