<?php

namespace App\Infrastructure\AI;

interface KnowledgeBaseTrainerInterface
{
    /**
     * Send brand wizard data to AI and return structured brand intelligence.
     *
     * @param  array<string, mixed>  $sourceData
     * @return array<string, mixed>
     */
    public function analyze(array $sourceData): array;
}
