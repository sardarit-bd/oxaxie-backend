<?php

namespace App\Repositories\Contracts;

use App\Models\AiProvider;
use Illuminate\Database\Eloquent\Collection;

interface AiProviderRepositoryInterface
{
    public function all(): Collection;
    
    public function findById(int $id): ?AiProvider;
    
    public function findBySlug(string $slug): ?AiProvider;
    
    public function getActive(): Collection;
    
    public function create(array $data): AiProvider;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function getWithCredentials(?int $userId = null): Collection;
    
    public function hasActiveCredential(int $providerId, ?int $userId = null): bool;
}