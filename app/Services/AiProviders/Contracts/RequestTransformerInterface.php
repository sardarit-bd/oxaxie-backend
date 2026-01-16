<?php

namespace App\Services\AiProviders\Contracts;

use App\Models\AiModel;

interface RequestTransformerInterface
{
    /**
     * Transform messages to provider-specific format
     */
    public function transform(
        array $messages,
        string $systemPrompt,
        AiModel $model,
        array $config = []
    ): array;
}