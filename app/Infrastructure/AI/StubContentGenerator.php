<?php

namespace App\Infrastructure\AI;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Models\Brand;

class StubContentGenerator implements ContentGeneratorInterface
{
    public function generate(Brand $brand, GenerateContentDTO $dto): array
    {
        return collect($dto->platforms)->map(fn (string $platform) => [
            'platform' => $platform,
            'body' => "Generated {$dto->contentType} for {$brand->name} on {$platform}.",
            'hashtags' => ['#cmoai', '#marketing'],
        ])->all();
    }
}
