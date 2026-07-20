<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Concerns\AnalyzesKnowledgeBaseViaChatApi;

class GroqKnowledgeBaseTrainer implements KnowledgeBaseTrainerInterface
{
    use AnalyzesKnowledgeBaseViaChatApi;

    public function __construct(private readonly StubKnowledgeBaseTrainer $fallback)
    {
    }

    public function analyze(array $sourceData): array
    {
        $apiKey = config('services.groq.api_key');

        if (! $apiKey) {
            return $this->fallback->analyze($sourceData);
        }

        $model = config('services.groq.model', 'llama-3.3-70b-versatile');

        $analysis = $this->analyzeViaChatApi(
            'groq',
            $apiKey,
            config('services.groq.base_url', 'https://api.groq.com/openai/v1'),
            $model,
            $sourceData,
        );

        return $analysis ?? $this->fallback->analyze($sourceData);
    }
}
