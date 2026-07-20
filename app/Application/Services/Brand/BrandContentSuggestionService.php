<?php

namespace App\Application\Services\Brand;

use App\Models\AiSuggestedPrompt;
use App\Models\Brand;
use App\Models\ContentItem;
use Illuminate\Support\Collection;

class BrandContentSuggestionService
{
    /** @var array<string, list<string>> */
    private const CATEGORY_TYPES = [
        'caption' => ['image_caption', 'hashtags', 'thread', 'post'],
        'image' => [],
        'reel' => ['reel_script'],
        'carousel' => ['carousel'],
    ];

    /** @var array<string, array{title: string, icon: string, color: string, bg: string}> */
    private const CATEGORY_META = [
        'caption' => [
            'title' => 'Caption Content Idea',
            'icon' => 'ti-align-left',
            'color' => '#5B4FC9',
            'bg' => '#EEF0FF',
        ],
        'image' => [
            'title' => 'Image Content Idea',
            'icon' => 'ti-photo',
            'color' => '#EC4899',
            'bg' => '#FDF2F8',
        ],
        'reel' => [
            'title' => 'Reels Content Ideas',
            'icon' => 'ti-player-play',
            'color' => '#EF4444',
            'bg' => '#FEF2F2',
        ],
        'carousel' => [
            'title' => 'Carousel Content Ideas',
            'icon' => 'ti-layout-columns',
            'color' => '#16A34A',
            'bg' => '#F0FDF4',
        ],
    ];

    public function buildForBrand(Brand $brand, array $aiAnalysis = []): array
    {
        $brand->loadMissing(['suggestedPrompts', 'voiceSettings', 'knowledgeBase']);

        $context = $this->buildContext($brand, $aiAnalysis);
        $stored = $brand->suggestedPrompts;
        $usedPromptTexts = $this->usedSuggestionPromptTexts($brand);
        $usedIds = [];
        $categories = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $matched = $this->matchStoredPrompts($stored, self::CATEGORY_TYPES[$key], $usedIds);
            $usedIds = array_merge($usedIds, $matched->pluck('id')->filter()->all());
            $generated = $this->generateTemplates($key, $context, 5 - $matched->count());
            $prompts = $matched->map(fn (array $p) => collect($p)->except('id')->all())
                ->concat($generated)
                ->filter(fn (array $prompt) => ! $this->isPromptUsed($prompt, $usedPromptTexts))
                ->values()
                ->all();

            $prompts = $this->backfillPrompts($key, $context, $prompts, $usedPromptTexts, 5);

            $categories[] = array_merge($meta, [
                'key' => $key,
                'prompts' => $this->normalizePrompts($prompts, $key),
            ]);
        }

        return $categories;
    }

    /** @return list<string> */
    private function usedSuggestionPromptTexts(Brand $brand): array
    {
        return ContentItem::query()
            ->where('brand_id', $brand->id)
            ->where('metadata->from_content_suggestion', true)
            ->pluck('generation_prompt')
            ->map(fn ($text) => trim((string) $text))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array{text?: string}  $prompt */
    private function isPromptUsed(array $prompt, array $usedTexts): bool
    {
        $text = trim((string) ($prompt['text'] ?? ''));

        return $text !== '' && in_array($text, $usedTexts, true);
    }

    /** @param  list<array<string, mixed>>  $existing */
    private function backfillPrompts(string $category, array $context, array $existing, array $usedTexts, int $limit): array
    {
        if (count($existing) >= $limit) {
            return array_slice($existing, 0, $limit);
        }

        $existingTexts = collect($existing)->pluck('text')->map(fn ($text) => trim((string) $text))->filter()->all();
        $pool = $this->generateTemplates($category, $context, 20)
            ->reject(fn (array $prompt) => $this->isPromptUsed($prompt, $usedTexts))
            ->reject(function (array $prompt) use ($existingTexts) {
                $text = trim((string) ($prompt['text'] ?? ''));

                return $text === '' || in_array($text, $existingTexts, true);
            });

        return collect($existing)
            ->concat($pool)
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildContext(Brand $brand, array $aiAnalysis): array
    {
        $voice = $brand->voiceSettings;
        $kb = $brand->knowledgeBase;
        $themes = data_get($aiAnalysis, 'content_strategy.themes', ['educational', 'promotional', 'brand culture']);

        return [
            'name' => $brand->name,
            'tone' => $kb?->detected_tone ?? $aiAnalysis['detected_tone'] ?? $brand->tone ?? 'Professional',
            'audience' => $kb?->detected_audience ?? $aiAnalysis['detected_audience'] ?? $voice?->target_audience ?? 'your target audience',
            'services' => $kb?->detected_services ?? $aiAnalysis['detected_services'] ?? $voice?->products_services ?? 'core offerings',
            'summary' => $aiAnalysis['brand_summary'] ?? $voice?->company_description ?? $brand->short_description ?? '',
            'industry' => $brand->industry ?? 'your industry',
            'themes' => is_array($themes) ? $themes : [$themes],
        ];
    }

    private function matchStoredPrompts(Collection $stored, array $types, array $excludeIds = []): Collection
    {
        return $stored
            ->filter(fn (AiSuggestedPrompt $prompt) => ! in_array($prompt->id, $excludeIds, true))
            ->filter(fn (AiSuggestedPrompt $prompt) => in_array($prompt->content_type, $types, true))
            ->map(fn (AiSuggestedPrompt $prompt) => [
                'id' => $prompt->id,
                'title' => $prompt->label,
                'text' => $prompt->prompt_text,
                'tags' => array_values(array_filter([
                    $prompt->content_type ? str_replace('_', ' ', $prompt->content_type) : null,
                    $prompt->platform,
                ])),
            ]);
    }

    private function generateTemplates(string $category, array $ctx, int $count): Collection
    {
        if ($count <= 0) {
            return collect();
        }

        $templates = match ($category) {
            'caption' => $this->captionTemplates($ctx),
            'image' => $this->imageTemplates($ctx),
            'reel' => $this->reelTemplates($ctx),
            'carousel' => $this->carouselTemplates($ctx),
            default => [],
        };

        return collect($templates)->take($count);
    }

    private function captionTemplates(array $ctx): array
    {
        $name = $ctx['name'];
        $tone = $ctx['tone'];
        $audience = $ctx['audience'];
        $services = $ctx['services'];

        return [
            [
                'title' => 'Caption: brand awareness',
                'text' => "Write a {$tone} Instagram caption for {$name} introducing the brand to {$audience}. Highlight what makes {$name} unique in {$ctx['industry']}. Include a strong hook and CTA.",
                'tags' => ['caption', 'instagram', 'awareness'],
            ],
            [
                'title' => 'Caption: product spotlight',
                'text' => "Create a {$tone} caption for {$name} promoting {$services}. Target {$audience}, focus on benefits, and end with a clear call to action.",
                'tags' => ['caption', 'product'],
            ],
            [
                'title' => 'Caption: engagement question',
                'text' => "Write an engaging {$tone} caption for {$name} that asks {$audience} a question related to {$services}. Encourage comments and shares.",
                'tags' => ['caption', 'engagement'],
            ],
            [
                'title' => 'Caption: educational tip',
                'text' => "Draft a {$tone} educational caption for {$name} sharing a useful tip about {$services} for {$audience}. Keep it concise and value-driven.",
                'tags' => ['caption', 'educational'],
            ],
            [
                'title' => 'Caption: promotional offer',
                'text' => "Write a {$tone} promotional caption for {$name} with urgency and excitement. Target {$audience}, mention {$services}, and include hashtags relevant to {$ctx['industry']}.",
                'tags' => ['caption', 'promotional'],
            ],
        ];
    }

    private function imageTemplates(array $ctx): array
    {
        $name = $ctx['name'];
        $tone = $ctx['tone'];
        $audience = $ctx['audience'];
        $theme = $ctx['themes'][0] ?? 'brand culture';

        return [
            [
                'title' => 'Image: hero brand visual',
                'text' => "Create an image post brief for {$name}: a clean hero visual showcasing the brand identity. Style: {$tone}. Audience: {$audience}. Include layout, colors, and overlay text suggestions.",
                'tags' => ['image', 'brand'],
            ],
            [
                'title' => 'Image: product showcase',
                'text' => "Design an image post concept for {$name} featuring {$ctx['services']}. Describe the scene, props, lighting, and caption overlay for {$audience}.",
                'tags' => ['image', 'product'],
            ],
            [
                'title' => 'Image: behind the scenes',
                'text' => "Plan a behind-the-scenes image post for {$name} that humanizes the brand for {$audience}. Suggest composition, mood, and {$tone} caption angle.",
                'tags' => ['image', 'bts'],
            ],
            [
                'title' => 'Image: customer story',
                'text' => "Create an image post brief for {$name} highlighting a customer success or testimonial visual. Tone: {$tone}. Make it relatable for {$audience}.",
                'tags' => ['image', 'testimonial'],
            ],
            [
                'title' => 'Image: seasonal campaign',
                'text' => "Design a seasonal image post for {$name} tied to {$theme} theme. Describe visual elements, headline text, and CTA for {$audience}.",
                'tags' => ['image', 'campaign'],
            ],
        ];
    }

    private function reelTemplates(array $ctx): array
    {
        $name = $ctx['name'];
        $tone = $ctx['tone'];
        $audience = $ctx['audience'];
        $theme = $ctx['themes'][1] ?? ($ctx['themes'][0] ?? 'promotional');

        return [
            [
                'title' => 'Reel: 3-second hook',
                'text' => "Write a 30-second reel script for {$name} with a bold hook in the first 3 seconds. Topic: {$ctx['services']}. Tone: {$tone}. Audience: {$audience}. Include scene directions, voiceover lines, background music cues, and CTA.",
                'tags' => ['reel', 'video'],
            ],
            [
                'title' => 'Reel: quick tip',
                'text' => "Create a 15–30s reel script for {$name} sharing one quick tip for {$audience} about {$ctx['industry']}. Keep it {$tone}, include voiceover narration and upbeat background music, and end with a follow CTA.",
                'tags' => ['reel', 'tips'],
            ],
            [
                'title' => 'Reel: product demo',
                'text' => "Script a product demo reel for {$name} showing {$ctx['services']} in action. Target {$audience}. Include hook, demo beats, voiceover narration, subtle background music, and closing CTA in a {$tone} voice.",
                'tags' => ['reel', 'demo'],
            ],
            [
                'title' => 'Reel: trend adaptation',
                'text' => "Adapt a trending reel format for {$name} to reach {$audience}. Theme: {$theme}. Tone: {$tone}. Provide shot list, on-screen text, voiceover script, and music style.",
                'tags' => ['reel', 'trending'],
            ],
            [
                'title' => 'Reel: brand story',
                'text' => "Write a 45-second reel script telling {$name}'s brand story for {$audience}. Tone: {$tone}. Structure: problem → solution → why us → CTA. Include voiceover narration and cinematic background music.",
                'tags' => ['reel', 'story'],
            ],
        ];
    }

    private function carouselTemplates(array $ctx): array
    {
        $name = $ctx['name'];
        $tone = $ctx['tone'];
        $audience = $ctx['audience'];

        return [
            [
                'title' => 'Carousel: educational slides',
                'text' => "Design a 5-slide Instagram carousel for {$name} educating {$audience} about {$ctx['services']}. Each slide: heading + 2–3 lines + visual direction. Tone: {$tone}.",
                'tags' => ['carousel', 'educational'],
            ],
            [
                'title' => 'Carousel: step-by-step guide',
                'text' => "Create a step-by-step carousel for {$name} walking {$audience} through how to use {$ctx['services']}. 5–7 slides with {$tone} copy and CTA on the last slide.",
                'tags' => ['carousel', 'guide'],
            ],
            [
                'title' => 'Carousel: myths vs facts',
                'text' => "Write a myths-vs-facts carousel for {$name} in {$ctx['industry']}. Target {$audience}. {$tone} tone. One myth/fact pair per slide with punchy headlines.",
                'tags' => ['carousel', 'myths'],
            ],
            [
                'title' => 'Carousel: feature breakdown',
                'text' => "Plan a feature breakdown carousel for {$name} highlighting key benefits of {$ctx['services']}. Audience: {$audience}. Slide 1 hook, slides 2–5 features, final slide CTA.",
                'tags' => ['carousel', 'features'],
            ],
            [
                'title' => 'Carousel: before & after',
                'text' => "Create a before-and-after carousel concept for {$name} showing transformation for {$audience}. {$tone} copy, strong visual contrast, and a compelling final CTA.",
                'tags' => ['carousel', 'results'],
            ],
        ];
    }

    private function normalizePrompts(array $prompts, string $category): array
    {
        return collect($prompts)->values()->map(function (array $prompt, int $index) use ($category) {
            return [
                'number' => $index + 1,
                'title' => $prompt['title'] ?? 'Content idea '.($index + 1),
                'text' => $prompt['text'] ?? '',
                'tags' => $prompt['tags'] ?? [$category],
            ];
        })->all();
    }
}
