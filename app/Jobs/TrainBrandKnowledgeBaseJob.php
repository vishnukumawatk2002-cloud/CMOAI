<?php

namespace App\Jobs;

use App\Application\Services\Brand\BrandKnowledgeBaseService;
use App\Models\Brand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class TrainBrandKnowledgeBaseJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $brandId)
    {
    }

    public function handle(BrandKnowledgeBaseService $service): void
    {
        $brand = Brand::query()->find($this->brandId);

        if (! $brand) {
            return;
        }

        try {
            $service->train($brand);
        } catch (\Throwable $e) {
            Log::error('Brand knowledge base training failed', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
