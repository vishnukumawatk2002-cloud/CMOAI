<?php

namespace App\Application\Services\Brand;

use App\Models\ContentItem;
use App\Models\ScheduledPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduledPublishService
{
    private const MAX_RETRIES = 3;

    private const RETRY_DELAY_MINUTES = 5;

    public function __construct(
        private readonly SocialPublishService $publisher,
        private readonly SocialConnectService $socialConnect,
    ) {
    }

    /** @return array{published: int, failed: int, skipped: int, errors: list<string>} */
    public function publishDuePosts(int $limit = 10): array
    {
        $summary = [
            'published' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $contentItems = ContentItem::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit($limit)
            ->get();

        foreach ($contentItems as $item) {
            $result = $this->publishContentItem($item);

            if ($result === 'published') {
                $summary['published']++;
            } elseif ($result === 'failed') {
                $summary['failed']++;
                $summary['errors'][] = "Item #{$item->id}: ".(string) data_get($item->fresh()->metadata, 'publish_failure_reason');
            } else {
                $summary['skipped']++;
            }

            if ($result === 'published' || $result === 'failed') {
                usleep(2_000_000);
            }
        }

        $scheduledPosts = ScheduledPost::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->whereHas('contentItem', fn ($query) => $query->where('status', '!=', 'published'))
            ->with(['contentItem.brand', 'socialAccount'])
            ->orderBy('scheduled_at')
            ->limit(max(0, $limit - $summary['published'] - $summary['failed']))
            ->get();

        foreach ($scheduledPosts as $scheduledPost) {
            $result = $this->publishScheduledPost($scheduledPost);

            if ($result === 'published') {
                $summary['published']++;
            } elseif ($result === 'failed') {
                $summary['failed']++;
            } else {
                $summary['skipped']++;
            }
        }

        return $summary;
    }

    private function publishContentItem(ContentItem $item): string
    {
        return DB::transaction(function () use ($item) {
            /** @var ContentItem|null $locked */
            $locked = ContentItem::query()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status !== 'scheduled') {
                return 'skipped';
            }

            if (! $locked->scheduled_at || $locked->scheduled_at->isFuture()) {
                return 'skipped';
            }

            try {
                $this->preparePlatformTokens($locked);
                $this->publisher->publish($locked);

                $meta = $locked->metadata ?? [];
                unset($meta['publish_failure_reason'], $meta['publish_retry_count'], $meta['publish_last_attempt_at']);
                $locked->update(['metadata' => $meta]);

                ScheduledPost::query()
                    ->where('content_item_id', $locked->id)
                    ->whereIn('status', ['pending', 'publishing'])
                    ->update([
                        'status' => 'published',
                        'published_at' => now(),
                        'external_post_id' => $locked->fresh()->external_post_id,
                        'external_post_url' => $locked->fresh()->external_post_url,
                        'last_attempt_at' => now(),
                    ]);

                return 'published';
            } catch (\Throwable $e) {
                return $this->handlePublishFailure($locked, $e);
            }
        });
    }

    private function handlePublishFailure(ContentItem $locked, \Throwable $e): string
    {
        $message = $e->getMessage();
        $meta = $locked->metadata ?? [];
        $retryCount = (int) data_get($meta, 'publish_retry_count', 0) + 1;
        $meta['publish_retry_count'] = $retryCount;
        $meta['publish_failure_reason'] = $message;
        $meta['publish_last_attempt_at'] = now()->toIso8601String();

        if ($this->isRetryableError($message) && $retryCount < self::MAX_RETRIES) {
            $locked->update([
                'status' => 'scheduled',
                'scheduled_at' => now()->addMinutes(self::RETRY_DELAY_MINUTES),
                'metadata' => $meta,
            ]);

            Log::warning('Scheduled publish retry queued', [
                'content_item_id' => $locked->id,
                'brand_id' => $locked->brand_id,
                'platform' => $locked->platform,
                'retry_count' => $retryCount,
                'error' => $message,
            ]);

            return 'skipped';
        }

        $locked->update([
            'status' => 'failed',
            'metadata' => $meta,
        ]);

        ScheduledPost::query()
            ->where('content_item_id', $locked->id)
            ->whereIn('status', ['pending', 'publishing'])
            ->get()
            ->each(function (ScheduledPost $scheduled) use ($message, $locked) {
                $scheduled->update([
                    'status' => 'failed',
                    'failure_reason' => $message,
                    'retry_count' => $scheduled->retry_count + 1,
                    'last_attempt_at' => now(),
                ]);
            });

        Log::warning('Scheduled publish failed', [
            'content_item_id' => $locked->id,
            'brand_id' => $locked->brand_id,
            'platform' => $locked->platform,
            'error' => $message,
        ]);

        return 'failed';
    }

    private function isRetryableError(string $message): bool
    {
        $needles = [
            'reduce the amount of data',
            'Application request limit',
            'API limit',
            'temporarily unavailable',
            'try again later',
            'An unknown error occurred',
        ];

        foreach ($needles as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function publishScheduledPost(ScheduledPost $scheduledPost): string
    {
        return DB::transaction(function () use ($scheduledPost) {
            /** @var ScheduledPost|null $locked */
            $locked = ScheduledPost::query()
                ->whereKey($scheduledPost->id)
                ->lockForUpdate()
                ->with(['contentItem.brand'])
                ->first();

            if (! $locked || $locked->status !== 'pending') {
                return 'skipped';
            }

            $contentItem = $locked->contentItem;

            if (! $contentItem || $contentItem->status === 'published') {
                $locked->update([
                    'status' => 'published',
                    'published_at' => $contentItem?->published_at ?? now(),
                    'last_attempt_at' => now(),
                ]);

                return 'skipped';
            }

            if ($locked->scheduled_at->isFuture()) {
                return 'skipped';
            }

            $locked->update([
                'status' => 'publishing',
                'last_attempt_at' => now(),
            ]);

            if ($contentItem->status !== 'scheduled') {
                $contentItem->update([
                    'status' => 'scheduled',
                    'scheduled_at' => $locked->scheduled_at,
                ]);
            }

            if ($locked->social_account_id && ! data_get($contentItem->metadata, 'social_account_id')) {
                $meta = $contentItem->metadata ?? [];
                $meta['social_account_id'] = $locked->social_account_id;
                $contentItem->update(['metadata' => $meta]);
                $contentItem->refresh();
            }

            try {
                $this->preparePlatformTokens($contentItem);
                $published = $this->publisher->publish($contentItem);

                $locked->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'external_post_id' => $published->external_post_id,
                    'external_post_url' => $published->external_post_url,
                    'failure_reason' => null,
                    'last_attempt_at' => now(),
                ]);

                return 'published';
            } catch (\Throwable $e) {
                return $this->handlePublishFailure($contentItem, $e);
            }
        });
    }

    private function preparePlatformTokens(ContentItem $item): void
    {
        $item->loadMissing('brand');

        if (! $item->brand) {
            return;
        }

        if (strtolower((string) $item->platform) === 'facebook') {
            $this->socialConnect->refreshFacebookAccountsForBrand($item->brand);
        }
    }
}
