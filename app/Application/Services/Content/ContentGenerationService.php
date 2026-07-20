<?php

namespace App\Application\Services\Content;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Infrastructure\AI\ContentGeneratorInterface;
use App\Jobs\GenerateContentJob;
use App\Models\AiGenerationRequest;
use App\Models\Brand;
use App\Models\User;

class ContentGenerationService
{
    public function __construct(
        private readonly ContentRepositoryInterface $content,
        private readonly ContentGeneratorInterface $generator,
    ) {
    }

    public function dispatch(User $user, Brand $brand, GenerateContentDTO $dto): AiGenerationRequest
    {
        $request = AiGenerationRequest::query()->create([
            'brand_id' => $brand->id,
            'user_id' => $user->id,
            'content_type' => $dto->contentType,
            'platforms' => $dto->platforms,
            'prompt' => $dto->prompt,
            'status' => 'processing',
        ]);

        if (config('cmo.queue_content_generation', true)) {
            GenerateContentJob::dispatch($request->id);
        } else {
            $this->process($request);
        }

        return $request;
    }

    public function generate(User $user, Brand $brand, GenerateContentDTO $dto): AiGenerationRequest
    {
        $request = AiGenerationRequest::query()->create([
            'brand_id' => $brand->id,
            'user_id' => $user->id,
            'content_type' => $dto->contentType,
            'platforms' => $dto->platforms,
            'prompt' => $dto->prompt,
            'status' => 'processing',
        ]);

        $this->process($request);

        return $request->load('contentItems');
    }

    public function process(AiGenerationRequest $request): void
    {
        $brand = Brand::query()->findOrFail($request->brand_id);

        $dto = new GenerateContentDTO(
            contentType: $request->content_type,
            platforms: $request->platforms,
            prompt: $request->prompt,
        );

        $variations = $this->generator->generate($brand, $dto);

        foreach ($variations as $index => $variation) {
            $this->content->create([
                'brand_id' => $brand->id,
                'ai_generation_request_id' => $request->id,
                'content_type' => $request->content_type,
                'platform' => $variation['platform'],
                'body' => $variation['body'],
                'status' => 'draft',
                'variation_number' => $index + 1,
                'generation_prompt' => $request->prompt,
            ]);
        }

        $request->update(['status' => 'complete', 'completed_at' => now()]);
    }
}
