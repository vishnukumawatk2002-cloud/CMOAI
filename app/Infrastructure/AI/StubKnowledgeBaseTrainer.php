<?php

namespace App\Infrastructure\AI;

class StubKnowledgeBaseTrainer implements KnowledgeBaseTrainerInterface
{
    public function analyze(array $sourceData): array
    {
        $business = $sourceData['business'] ?? [];
        $keywords = $business['keywords'] ?? [];
        $keywordList = is_array($keywords) ? $keywords : array_filter(array_map('trim', explode(',', (string) $keywords)));

        $assetCount = count($sourceData['assets'] ?? []);
        $socialCount = count($sourceData['social_accounts'] ?? []);
        $brandName = $business['name'] ?? 'Brand';

        return [
            'provider' => 'local',
            'brand_summary' => trim(sprintf(
                '%s operates in %s. %s',
                $brandName,
                $business['industry'] ?? 'their industry',
                $business['description'] ?? ''
            )),
            'detected_tone' => $business['tone'] ?? null,
            'detected_audience' => $business['target_audience'] ?? null,
            'detected_services' => $business['products_services'] ?? null,
            'top_keywords' => array_values($keywordList),
            'content_strategy' => [
                'themes' => ['educational', 'promotional', 'brand culture'],
                'pillars' => array_values(array_filter([
                    $business['products_services'] ?? null,
                    $business['target_audience'] ? 'Audience: '.$business['target_audience'] : null,
                ])),
            ],
            'suggested_prompts' => [
                [
                    'label' => '30-day content calendar',
                    'content_type' => 'thirty_day_plan',
                    'platform' => 'instagram',
                    'prompt_text' => "Create a 30-day social media content calendar for {$brandName} targeting ".($business['target_audience'] ?? 'their audience').'.',
                ],
                [
                    'label' => 'Reel ideas',
                    'content_type' => 'reel_script',
                    'platform' => 'instagram',
                    'prompt_text' => "Generate 10 short-form reel ideas for {$brandName} in a ".($business['tone'] ?? 'professional').' tone.',
                ],
                [
                    'label' => 'Carousel post',
                    'content_type' => 'carousel',
                    'platform' => 'linkedin',
                    'prompt_text' => "Write a LinkedIn carousel outline about {$brandName}'s core services.",
                ],
            ],
            'indexed_assets' => $assetCount,
            'connected_social_accounts' => $socialCount,
        ];
    }
}
