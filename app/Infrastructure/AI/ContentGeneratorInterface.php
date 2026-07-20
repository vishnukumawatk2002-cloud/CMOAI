<?php

namespace App\Infrastructure\AI;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Models\Brand;

interface ContentGeneratorInterface
{
    /**
     * @return array<int, array{platform: string, body: string, hashtags?: array<string>}>
     */
    public function generate(Brand $brand, GenerateContentDTO $dto): array;
}
