<?php

namespace App\Jobs;

use App\Application\DTOs\Content\GenerateContentDTO;
use App\Application\Services\Content\ContentGenerationService;
use App\Models\AiGenerationRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $generationRequestId)
    {
    }

    public function handle(ContentGenerationService $service): void
    {
        $request = AiGenerationRequest::query()->find($this->generationRequestId);

        if (! $request) {
            return;
        }

        try {
            $service->process($request);
        } catch (\Throwable $e) {
            $request->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Content generation failed', [
                'request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
