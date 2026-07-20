<?php

namespace App\Providers;

use App\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Contracts\Repositories\UserRepositoryInterface;
use App\Infrastructure\AI\BluesmindsKnowledgeBaseTrainer;
use App\Infrastructure\AI\ContentGeneratorInterface;
use App\Infrastructure\AI\GeminiKnowledgeBaseTrainer;
use App\Infrastructure\AI\OpenRouterKnowledgeBaseTrainer;
// use App\Infrastructure\AI\GroqKnowledgeBaseTrainer;
use App\Infrastructure\AI\KnowledgeBaseTrainerInterface;
use App\Infrastructure\AI\OpenAiKnowledgeBaseTrainer;
use App\Infrastructure\AI\StubContentGenerator;
use App\Infrastructure\AI\StubKnowledgeBaseTrainer;
use App\Infrastructure\Repositories\EloquentBrandRepository;
use App\Infrastructure\Repositories\EloquentContentRepository;
use App\Infrastructure\Repositories\EloquentSubscriptionRepository;
use App\Infrastructure\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public array $bindings = [
        UserRepositoryInterface::class => EloquentUserRepository::class,
        BrandRepositoryInterface::class => EloquentBrandRepository::class,
        ContentRepositoryInterface::class => EloquentContentRepository::class,
        SubscriptionRepositoryInterface::class => EloquentSubscriptionRepository::class,
        ContentGeneratorInterface::class => StubContentGenerator::class,
        KnowledgeBaseTrainerInterface::class => OpenRouterKnowledgeBaseTrainer::class,
    ];

    public function register(): void
    {
        $this->app->singleton(StubKnowledgeBaseTrainer::class);

        foreach ($this->bindings as $abstract => $concrete) {
            if ($abstract === KnowledgeBaseTrainerInterface::class) {
                $this->app->bind($abstract, function ($app) {
                    if (config('services.openrouter.api_key')) {
                        return $app->make(OpenRouterKnowledgeBaseTrainer::class);
                    }

                    if (config('services.gemini.api_key')) {
                        return $app->make(GeminiKnowledgeBaseTrainer::class);
                    }

                    if (config('services.bluesminds.api_key')) {
                        return $app->make(BluesmindsKnowledgeBaseTrainer::class);
                    }

                    if (config('services.openai.api_key')) {
                        return $app->make(OpenAiKnowledgeBaseTrainer::class);
                    }

                    return $app->make(StubKnowledgeBaseTrainer::class);
                });

                continue;
            }

            $this->app->bind($abstract, $concrete);
        }
    }
}
