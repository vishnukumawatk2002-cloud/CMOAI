<?php

namespace App\Application\Services\Brand;

use App\Models\ContentItem;
use RuntimeException;

class SocialPublishService
{
    public function __construct(
        private readonly FacebookPublishService $facebook,
        private readonly InstagramPublishService $instagram,
        private readonly LinkedInPublishService $linkedin,
        private readonly XPublishService $x,
        private readonly YouTubePublishService $youtube,
        private readonly SnapchatPublishService $snapchat,
    ) {
    }

    public function publish(ContentItem $contentItem): ContentItem
    {
        $platform = strtolower((string) $contentItem->platform);

        $result = match ($platform) {
            'facebook' => $this->facebook->publish($contentItem),
            'instagram' => $this->instagram->publish($contentItem),
            'linkedin' => $this->linkedin->publish($contentItem),
            'x', 'twitter' => $this->x->publish($contentItem),
            'youtube' => $this->youtube->publish($contentItem),
            'snapchat' => $this->snapchat->publish($contentItem),
            default => throw new RuntimeException(
                'Publishing to '.ucfirst($platform).' is not available yet.'
            ),
        };

        $contentItem->update([
            'status' => 'published',
            'published_at' => now(),
            'external_post_id' => $result['id'] ?: null,
            'external_post_url' => $result['url'] ?? null,
        ]);

        if (! empty($result['warning'])) {
            session()->flash('publish_warning', (string) $result['warning']);
        }

        return $contentItem->fresh();
    }
}
