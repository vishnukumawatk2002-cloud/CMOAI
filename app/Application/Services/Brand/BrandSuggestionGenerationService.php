<?php

namespace App\Application\Services\Brand;

use App\Infrastructure\AI\OpenAiCompatibleChatService;
use App\Infrastructure\AI\OpenRouterImageGenerationService;
use App\Infrastructure\AI\OpenRouterVideoGenerationService;
use App\Models\Brand;
use App\Models\ContentItem;
use Illuminate\Support\Str;

class BrandSuggestionGenerationService
{
    /** @var array<string, string> */
    private const CONTENT_TYPES = [
        'caption' => 'post',
        'image' => 'post',
        'reel' => 'reel_script',
        'carousel' => 'carousel',
    ];

    public function __construct(
        private readonly OpenAiCompatibleChatService $chat,
        private readonly OpenRouterImageGenerationService $images,
        private readonly OpenRouterVideoGenerationService $videos,
    ) {
    }

    /** @return array{content: string, provider: string, model: string}|null */
    public function generatePreview(Brand $brand, string $category, string $promptText): ?array
    {
        $promptText = trim($promptText);

        if ($promptText === '') {
            return null;
        }

        if ($category === 'image') {
            return $this->chat->complete(
                <<<PROMPT
You are CMO AI for {$brand->name}. Convert the marketing brief into a detailed image post brief ready for a designer or AI image tool.
Include visual concept, composition, colors, mood, and caption hook. Return only the final brief text.
PROMPT,
                $promptText,
            );
        }

        if ($category === 'reel') {
            return $this->chat->complete(
                <<<PROMPT
You are CMO AI for {$brand->name}. Write a complete Instagram Reel script from the brief.
Include hook (first 3 seconds), scene beats, on-screen text, voiceover, and CTA. Return only the final script.
PROMPT,
                $promptText,
            );
        }

        if ($category === 'carousel') {
            return $this->chat->complete(
                <<<PROMPT
You are CMO AI for {$brand->name}. Write carousel slide copy from the brief.
Include slide-by-slide headings and body text for each slide plus a closing CTA. Return only the final carousel copy.
PROMPT,
                $promptText,
            );
        }

        return $this->chat->complete(
            $this->systemPromptForCategory($category, $brand),
            $promptText,
        );
    }

    /** @return array{content: string, provider: string, model: string}|null */
    public function regeneratePromptText(Brand $brand, string $category, string $title, string $currentText = ''): ?array
    {
        $brand->loadMissing(['voiceSettings', 'knowledgeBase']);

        $aiAnalysis = data_get($brand->knowledgeBase?->source_data, 'ai_analysis', []);
        $voice = $brand->voiceSettings;

        $tone = $brand->knowledgeBase?->detected_tone
            ?? $aiAnalysis['detected_tone']
            ?? $brand->tone
            ?? 'Professional';
        $audience = $brand->knowledgeBase?->detected_audience
            ?? $aiAnalysis['detected_audience']
            ?? $voice?->target_audience
            ?? 'target audience';
        $services = $brand->knowledgeBase?->detected_services
            ?? $aiAnalysis['detected_services']
            ?? $voice?->products_services
            ?? 'core offerings';
        $summary = $aiAnalysis['brand_summary']
            ?? $voice?->company_description
            ?? $brand->short_description
            ?? '';
        $industry = $brand->industry ?? 'their industry';

        $categoryGuide = match ($category) {
            'image' => 'an image post brief with visual direction, layout, colors, mood, and overlay text suggestions',
            'reel' => 'a short-form Reel/Short video script prompt with hook, scenes, on-screen text, voiceover narration, background music cues, and CTA',
            'carousel' => 'a carousel post prompt with slide-by-slide guidance and CTA',
            default => 'a social media caption prompt with hook, body direction, and CTA',
        };

        $systemPrompt = <<<PROMPT
You are CMO AI for {$brand->name}. Write ONE fresh, ready-to-use content prompt brief for a marketer.
Topic: {$title}
Content type: {$category} — {$categoryGuide}
Brand tone: {$tone}
Audience: {$audience}
Services: {$services}
Industry: {$industry}
Brand summary: {$summary}
Return only the final prompt text. No markdown, no quotes, no title prefix, no bullet list unless essential.
Make it clearly different from any previous version while staying on-brand.
PROMPT;

        $userPrompt = trim($currentText) !== ''
            ? "Replace this previous prompt with a fresh on-brand version:\n{$currentText}"
            : "Generate a new on-brand prompt for {$title}.";

        return $this->chat->complete($systemPrompt, $userPrompt, 0.85);
    }

    /** @param  list<array{category: string, title: string, text: string}>  $prompts */
    public function generateFromPrompts(Brand $brand, array $prompts): int
    {
        $saved = 0;

        foreach ($prompts as $prompt) {
            $category = (string) ($prompt['category'] ?? 'caption');
            $title = trim((string) ($prompt['title'] ?? 'Generated content'));
            $promptText = trim((string) ($prompt['text'] ?? ''));

            if ($promptText === '') {
                continue;
            }

            if ($category === 'image') {
                if ($this->generateImageSuggestion($brand, $prompt, $category, $title, $promptText)) {
                    $saved++;
                }

                continue;
            }

            if ($category === 'carousel') {
                if ($this->generateCarouselSuggestion($brand, $prompt, $category, $title, $promptText)) {
                    $saved++;
                }

                continue;
            }

            if ($category === 'reel') {
                if ($this->generateReelSuggestion($brand, $prompt, $category, $title, $promptText)) {
                    $saved++;
                }

                continue;
            }

            $result = $this->chat->complete(
                $this->systemPromptForCategory($category, $brand),
                $promptText,
            );

            if (! $result) {
                continue;
            }

            ContentItem::query()->create([
                'brand_id' => $brand->id,
                'content_type' => self::CONTENT_TYPES[$category] ?? 'post',
                'platform' => $this->resolvePlatform($prompt, $category),
                'title' => Str::limit($title, 120),
                'body' => $result['content'],
                'status' => 'draft',
                'generation_prompt' => $promptText,
                'metadata' => [
                    'from_content_suggestion' => true,
                    'suggestion_category' => $category,
                    'suggestion_title' => $title,
                    'ai_provider' => $result['provider'],
                    'ai_model' => $result['model'],
                ],
            ]);

            $saved++;
        }

        return $saved;
    }

    /** @param  array{category: string, title: string, text: string, tags?: list<string>}  $prompt */
    private function generateImageSuggestion(
        Brand $brand,
        array $prompt,
        string $category,
        string $title,
        string $promptText,
    ): bool {
        $imagePrompt = $this->buildImageGenerationPrompt($brand, $promptText);
        $generated = $this->images->generateForBrand($brand, $imagePrompt, $title);
        $asset = $generated['asset'];

        ContentItem::query()->create([
            'brand_id' => $brand->id,
            'content_type' => self::CONTENT_TYPES[$category],
            'platform' => $this->resolvePlatform($prompt, $category),
            'title' => Str::limit($title, 120),
            'body' => Str::limit($imagePrompt, 500),
            'status' => 'draft',
            'generation_prompt' => $promptText,
            'metadata' => [
                'from_content_suggestion' => true,
                'suggestion_category' => $category,
                'suggestion_title' => $title,
                'asset_id' => $asset->id,
                'image_prompt' => $imagePrompt,
                'ai_provider' => $generated['provider'],
                'ai_model' => $generated['model'],
            ],
        ]);

        return true;
    }

    /** @param  array{category: string, title: string, text: string, tags?: list<string>}  $prompt */
    private function generateCarouselSuggestion(
        Brand $brand,
        array $prompt,
        string $category,
        string $title,
        string $promptText,
    ): bool {
        $slidePrompts = $this->buildCarouselSlidePrompts($brand, $promptText);
        $generatedSlides = $this->images->generateCarouselForBrand($brand, $slidePrompts, $title);

        if ($generatedSlides === []) {
            return false;
        }

        $first = $generatedSlides[0];
        $assetIds = collect($generatedSlides)->map(fn (array $slide) => $slide['asset']->id)->all();
        $carouselGroup = (string) data_get($first['asset']->metadata, 'carousel_group');

        ContentItem::query()->create([
            'brand_id' => $brand->id,
            'content_type' => self::CONTENT_TYPES[$category],
            'platform' => $this->resolvePlatform($prompt, $category),
            'title' => Str::limit($title, 120),
            'body' => Str::limit($slidePrompts[0] ?? $promptText, 500),
            'status' => 'draft',
            'generation_prompt' => $promptText,
            'metadata' => [
                'from_content_suggestion' => true,
                'suggestion_category' => $category,
                'suggestion_title' => $title,
                'carousel_group' => $carouselGroup,
                'carousel_asset_ids' => $assetIds,
                'asset_id' => $first['asset']->id,
                'carousel_slide_prompts' => $slidePrompts,
                'ai_provider' => $first['provider'],
                'ai_model' => $first['model'],
            ],
        ]);

        return true;
    }

    /** @param  array{category: string, title: string, text: string, tags?: list<string>}  $prompt */
    private function generateReelSuggestion(
        Brand $brand,
        array $prompt,
        string $category,
        string $title,
        string $promptText,
    ): bool {
        $videoPrompt = $this->buildVideoGenerationPrompt($brand, $promptText);
        $generated = $this->videos->generateForBrand($brand, $videoPrompt, $title);
        $asset = $generated['asset'];

        ContentItem::query()->create([
            'brand_id' => $brand->id,
            'content_type' => self::CONTENT_TYPES[$category],
            'platform' => $this->resolvePlatform($prompt, $category),
            'title' => Str::limit($title, 120),
            'body' => Str::limit($videoPrompt, 500),
            'status' => 'draft',
            'generation_prompt' => $promptText,
            'metadata' => [
                'from_content_suggestion' => true,
                'suggestion_category' => $category,
                'suggestion_title' => $title,
                'asset_id' => $asset->id,
                'video_url' => route('app.brand.assets.show', $asset),
                'video_prompt' => $videoPrompt,
                'ai_provider' => $generated['provider'],
                'ai_model' => $generated['model'],
            ],
        ]);

        return true;
    }

    /** @return list<string> */
    private function buildCarouselSlidePrompts(Brand $brand, string $promptText): array
    {
        $slideCount = max(1, (int) config('services.openrouter.carousel_slides', 4));

        try {
            $result = $this->chat->complete(
                <<<PROMPT
You create image prompts for an Instagram carousel for {$brand->name}.
Return JSON only in this exact shape:
{"slides":[{"label":"Slide 1","prompt":"..."}]}
Provide exactly {$slideCount} slides. Each prompt must describe one unique visual for a square social image.
No markdown, no overlay text instructions in the image itself unless essential.
PROMPT,
                $promptText,
                0.6,
                ['type' => 'json_object'],
            );

            if ($result) {
                $decoded = json_decode($result['content'], true);

                if (is_array($decoded)) {
                    $prompts = collect($decoded['slides'] ?? [])
                        ->map(fn ($slide) => is_array($slide) ? trim((string) ($slide['prompt'] ?? '')) : '')
                        ->filter()
                        ->values()
                        ->all();

                    if ($prompts !== []) {
                        return array_slice($prompts, 0, $slideCount);
                    }
                }
            }
        } catch (\Throwable) {
            // Fall back below.
        }

        $basePrompt = $this->buildImageGenerationPrompt($brand, $promptText);
        $prompts = [];

        for ($i = 1; $i <= $slideCount; $i++) {
            $prompts[] = "{$basePrompt}. Carousel slide {$i} of {$slideCount}, unique composition, cohesive brand style.";
        }

        return $prompts;
    }

    private function buildImageGenerationPrompt(Brand $brand, string $promptText): string
    {
        try {
            $result = $this->chat->complete(
                <<<PROMPT
You write concise prompts for AI image generators.
Convert the marketing brief into ONE photorealistic social-media image prompt for {$brand->name}.
Include scene, style, lighting, and mood. No markdown, no quotes, no bullet points.
Return only the image prompt text, max 400 characters.
PROMPT,
                $promptText,
                0.6,
            );

            if ($result && filled($result['content'])) {
                return Str::limit(trim($result['content']), 400, '');
            }
        } catch (\Throwable) {
            // Fall back to the original brief if prompt refinement fails.
        }

        return Str::limit($promptText, 400, '');
    }

    private function buildVideoGenerationPrompt(Brand $brand, string $promptText): string
    {
        $duration = max(1, (int) config('services.openrouter.video_duration', 8));

        try {
            $result = $this->chat->complete(
                <<<PROMPT
You write concise prompts for AI vertical short-form video generators.
Convert the marketing brief into ONE cinematic {$duration}-second Instagram Reel video prompt for {$brand->name}.
Include subject, camera movement, setting, lighting, mood, voiceover or spoken dialogue, and background music or ambient audio.
The generated video must include synchronized audio. No markdown, no quotes, no bullet points.
Return only the video prompt text, max 500 characters.
PROMPT,
                $promptText,
                0.6,
            );

            if ($result && filled($result['content'])) {
                return Str::limit(trim($result['content']), 500, '');
            }
        } catch (\Throwable) {
            // Fall back below.
        }

        return Str::limit($promptText, 500, '');
    }

    private function systemPromptForCategory(string $category, Brand $brand): string
    {
        $brandName = $brand->name;

        return match ($category) {
            default => "You are CMO AI for {$brandName}. Write a complete social media caption ready to publish. Include hook, body, hashtags if relevant. Return only the final caption text.",
        };
    }

    /** @param  array{category: string, title: string, text: string, tags?: list<string>}  $prompt */
    private function resolvePlatform(array $prompt, string $category): string
    {
        $fromText = $this->guessPlatformFromText($prompt);

        if ($fromText !== null) {
            return $fromText;
        }

        foreach ($prompt['tags'] ?? [] as $tag) {
            $fromTag = $this->guessPlatformFromText(['text' => (string) $tag, 'title' => '']);

            if ($fromTag !== null) {
                return $fromTag;
            }
        }

        return match ($category) {
            'reel', 'carousel' => 'instagram',
            default => 'multi',
        };
    }

    /** @param  array{title?: string, text?: string}  $prompt */
    private function guessPlatformFromText(array $prompt): ?string
    {
        $haystack = strtolower(($prompt['text'] ?? '').' '.($prompt['title'] ?? ''));

        foreach (['instagram', 'facebook', 'linkedin', 'youtube', 'x', 'twitter'] as $platform) {
            if (str_contains($haystack, $platform)) {
                return $platform === 'twitter' ? 'x' : $platform;
            }
        }

        return null;
    }
}
