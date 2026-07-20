<?php

namespace App\Application\DTOs\Content;

readonly class GenerateContentDTO
{
    public function __construct(
        public string $contentType,
        public array $platforms,
        public string $prompt,
    ) {}
}
