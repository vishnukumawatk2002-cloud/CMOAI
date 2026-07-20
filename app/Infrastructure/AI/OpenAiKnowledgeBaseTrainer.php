<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Concerns\AnalyzesKnowledgeBaseViaChatApi;

class OpenAiKnowledgeBaseTrainer implements KnowledgeBaseTrainerInterface
{
    use AnalyzesKnowledgeBaseViaChatApi;

    public function __construct(private readonly StubKnowledgeBaseTrainer $fallback)
    {
    }

    public function analyze(array $sourceData): array
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return $this->fallback->analyze($sourceData);
        }

        $analysis = $this->analyzeViaChatApi(
            'openai',
            $apiKey,
            config('services.openai.base_url', 'https://api.openai.com/v1'),
            config('services.openai.model', 'gpt-4o-mini'),
            $sourceData,
        );

        return $analysis ?? $this->fallback->analyze($sourceData);
    }
}
