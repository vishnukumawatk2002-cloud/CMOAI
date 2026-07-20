<?php

namespace App\Application\DTOs\Brand;

readonly class CreateBrandDTO
{
    public function __construct(
        public string $name,
        public ?string $website,
        public string $industry,
        public string $country,
        public ?string $language = 'English',
        public ?string $tone = null,
        public ?string $logoPath = null,
    ) {}
}
