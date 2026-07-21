<?php

namespace App\Console\Commands;

use App\Application\Services\Brand\ScheduledPublishService;
use Illuminate\Console\Command;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'cmo:publish-scheduled {--limit=10 : Maximum posts to publish per run}';

    protected $description = 'Publish scheduled content items when their scheduled time arrives';

    public function handle(ScheduledPublishService $publisher): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $summary = $publisher->publishDuePosts($limit);

        $this->info(sprintf(
            'Scheduled publish complete: %d published, %d failed, %d skipped.',
            $summary['published'],
            $summary['failed'],
            $summary['skipped'],
        ));

        return self::SUCCESS;
    }
}
