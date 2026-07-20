<?php

namespace App\Infrastructure\AI;

use App\Infrastructure\AI\Concerns\AnalyzesKnowledgeBaseViaChatApi;

class BluesmindsKnowledgeBaseTrainer implements KnowledgeBaseTrainerInterface
{
    use AnalyzesKnowledgeBaseViaChatApi;

    public function __construct(
        private readonly StubKnowledgeBaseTrainer $fallback,
        private readonly OpenAiCompatibleChatService $chat,
    ) {
    }

    public function analyze(array $sourceData): array
    {
        if (! config('services.bluesminds.api_key')) {
            return $this->fallback->analyze($sourceData);
        }

        $analysis = $this->analyzeViaChatService($this->chat, 'bluesminds', $sourceData);

        return $analysis ?? $this->fallback->analyze($sourceData);
    }
}
