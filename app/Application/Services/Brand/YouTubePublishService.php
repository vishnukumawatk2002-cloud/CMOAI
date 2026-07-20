<?php

namespace App\Application\Services\Brand;

use App\Models\BrandAsset;
use App\Models\ContentItem;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class YouTubePublishService
{
    public function __construct(private readonly SocialConnectService $connect)
    {
    }

    /** @return array{id: string, url: string|null, warning?: string} */
    public function publish(ContentItem $contentItem): array
    {
        [$account, $token] = $this->resolveCredentials($contentItem);

        try {
            $token = $this->connect->ensureFreshToken($account);
        } catch (\Throwable) {
            // Use stored token if refresh fails.
        }

        $postType = $this->resolvePostType($contentItem);
        $title = trim($contentItem->title ?? '') ?: 'YouTube post';
        $description = trim($contentItem->body ?? '');

        $videoId = match ($postType) {
            'reel' => $this->publishShort($contentItem, $token, $title, $description),
            'carousel' => throw new RuntimeException($this->communityPostsUnsupportedMessage('carousel')),
            default => $this->publishImageOrVideoPost($contentItem, $token, $title, $description),
        };

        $isShort = $postType === 'reel';

        return [
            'id' => $videoId,
            'url' => $videoId !== ''
                ? ($isShort
                    ? "https://www.youtube.com/shorts/{$videoId}"
                    : "https://www.youtube.com/watch?v={$videoId}")
                : null,
        ];
    }

    private function publishShort(ContentItem $item, string $token, string $title, string $description): string
    {
        $asset = $this->resolveVideoAsset($item);
        $publicVideoUrl = $this->resolvePublicVideoUrl($item);

        if ($asset && $this->isVideoAsset($asset)) {
            $videoId = $this->uploadVideoFromAsset($token, $asset, $title, $this->withShortsTag($description));

            return $videoId;
        }

        if ($publicVideoUrl) {
            return $this->uploadVideoFromUrl($token, $publicVideoUrl, $title, $this->withShortsTag($description));
        }

        throw new RuntimeException(
            'YouTube Shorts ke liye vertical .mp4 video chahiye (5–60 sec). Image se Shorts nahi banta — Post planning mein Reel/video select karo.'
        );
    }

    private function publishImageOrVideoPost(ContentItem $item, string $token, string $title, string $description): string
    {
        $asset = $this->resolveImageAsset($item) ?? $this->resolveVideoAsset($item);

        if ($asset && $this->isVideoAsset($asset)) {
            return $this->uploadVideoFromAsset($token, $asset, $title, $this->withoutShortsTag($description));
        }

        $publicVideoUrl = $this->resolvePublicVideoUrl($item);

        if ($publicVideoUrl) {
            return $this->uploadVideoFromUrl($token, $publicVideoUrl, $title, $this->withoutShortsTag($description));
        }

        throw new RuntimeException($this->communityPostsUnsupportedMessage('image'));
    }

    private function communityPostsUnsupportedMessage(string $kind): string
    {
        $label = $kind === 'carousel' ? 'Carousel images' : 'Image posts';

        return $label.' YouTube ke Community / Posts tab pe API se publish nahi ho sakte — Google ne ye feature official API mein diya hi nahi hai. '
            .'Studio mein manually post karo: https://studio.youtube.com → Create → Create a post (Image). '
            .'CMO AI se YouTube pe sirf Video file (Videos tab) ya Reel (Shorts) publish ho sakte hain.';
    }

    private function publishCarousel(ContentItem $item, string $token, string $title, string $description): string
    {
        throw new RuntimeException($this->communityPostsUnsupportedMessage('carousel'));
    }

    private function resolvePostType(ContentItem $item): string
    {
        $postType = data_get($item->metadata, 'post_type', 'image');

        if ($postType === 'reel' || $item->content_type === 'reel_script') {
            return 'reel';
        }

        if ($postType === 'carousel' || $item->content_type === 'carousel') {
            return 'carousel';
        }

        return 'image';
    }

    /** @return list<string> */
    private function resolveImagePaths(ContentItem $item): array
    {
        $paths = [];

        foreach ($this->resolveCarouselImageAssets($item) as $asset) {
            if ($this->isImageAsset($asset)) {
                $paths[] = $this->assetPath($asset);
            }
        }

        if ($paths !== []) {
            return $paths;
        }

        $imageAsset = $this->resolveImageAsset($item);

        if ($imageAsset && $this->isImageAsset($imageAsset)) {
            return [$this->assetPath($imageAsset)];
        }

        foreach ($this->carouselImageUrls($item) as $url) {
            $url = (string) $url;

            if ($this->assetFromUrl($item->brand_id, $url)) {
                continue;
            }

            if ($this->isPublicHttpUrl($url)) {
                $paths[] = $this->downloadToTemp($url);
            }
        }

        $thumb = data_get($item->metadata, 'thumbnail_url');

        if ($paths === [] && is_string($thumb) && $thumb !== '') {
            $asset = $this->assetFromUrl($item->brand_id, $thumb);

            if ($asset && $this->isImageAsset($asset)) {
                $paths[] = $this->assetPath($asset);
            } elseif ($this->isPublicHttpUrl($thumb)) {
                $paths[] = $this->downloadToTemp($thumb);
            }
        }

        return $paths;
    }

    /** @return list<BrandAsset> */
    private function resolveCarouselImageAssets(ContentItem $item): array
    {
        $brandId = $item->brand_id;
        $assets = [];
        $seenIds = [];

        foreach ($this->carouselImageUrls($item) as $url) {
            $asset = $this->assetFromUrl($brandId, (string) $url);

            if ($asset && ! in_array($asset->id, $seenIds, true)) {
                $assets[] = $asset;
                $seenIds[] = $asset->id;
            }
        }

        if ($assets !== []) {
            return $assets;
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType === 'carousel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('metadata->carousel_group', $manualKey)
                ->orderBy('metadata->slot')
                ->get()
                ->all();
        }

        return [];
    }

    private function resolveImageAsset(ContentItem $item): ?BrandAsset
    {
        $brandId = $item->brand_id;
        $thumb = data_get($item->metadata, 'thumbnail_url');

        if (is_string($thumb) && $thumb !== '') {
            $asset = $this->assetFromUrl($brandId, $thumb);

            if ($asset) {
                return $asset;
            }
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType === 'image' && filled($manualKey) && ctype_digit((string) $manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $brandId)
                ->where('id', (int) $manualKey)
                ->first();
        }

        if ($manualType === 'caption' && filled($manualKey)) {
            $query = BrandAsset::query()->where('brand_id', $brandId);
            $assets = ctype_digit((string) $manualKey)
                ? $query->where('id', (int) $manualKey)->get()
                : $query->where('metadata->content_group', $manualKey)->get();

            return $assets->first(fn (BrandAsset $asset) => $this->isImageAsset($asset));
        }

        return null;
    }

    /** @param  list<string>  $imagePaths */
    private function buildSlideshowVideo(array $imagePaths, bool $vertical, int $secondsPerSlide = 8): string
    {
        if (! $this->ffmpegAvailable()) {
            throw new RuntimeException(
                'FFmpeg is required to publish image/carousel posts to YouTube. Install ffmpeg on the server, or publish a video file instead.'
            );
        }

        $tempDir = storage_path('app/temp/youtube');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $scale = $vertical
            ? 'scale=1080:1920:force_original_aspect_ratio=decrease,pad=1080:1920:(ow-iw)/2:(oh-ih)/2'
            : 'scale=1920:1080:force_original_aspect_ratio=decrease,pad=1920:1080:(ow-iw)/2:(oh-ih)/2';

        $segments = [];

        foreach ($imagePaths as $index => $imagePath) {
            $segment = $tempDir.'/yt-seg-'.uniqid('', true).'-'.$index.'.mp4';
            $this->runFfmpeg([
                'ffmpeg', '-y', '-loop', '1', '-i', $imagePath,
                '-c:v', 'libx264', '-t', (string) $secondsPerSlide,
                '-pix_fmt', 'yuv420p', '-vf', $scale,
                $segment,
            ]);
            $segments[] = $segment;
        }

        $output = $tempDir.'/yt-upload-'.uniqid('', true).'.mp4';

        if (count($segments) === 1) {
            rename($segments[0], $output);

            return $output;
        }

        $listFile = $tempDir.'/yt-concat-'.uniqid('', true).'.txt';
        $listBody = implode("\n", array_map(
            fn (string $file) => "file '".str_replace("'", "'\\''", $file)."'",
            $segments
        ));
        file_put_contents($listFile, $listBody);

        try {
            $this->runFfmpeg([
                'ffmpeg', '-y', '-f', 'concat', '-safe', '0', '-i', $listFile,
                '-c', 'copy', $output,
            ]);
        } finally {
            @unlink($listFile);

            foreach ($segments as $segment) {
                @unlink($segment);
            }
        }

        return $output;
    }

    private function uploadVideoFile(string $token, string $filePath, string $title, string $description): string
    {
        $contents = file_get_contents($filePath);

        if ($contents === false) {
            throw new RuntimeException('Could not read generated video for YouTube upload.');
        }

        return $this->resumableUpload($token, $contents, 'video/mp4', $title, $description);
    }

    /** @return array{0: SocialAccount, 1: string} */
    private function resolveCredentials(ContentItem $contentItem): array
    {
        $accountId = data_get($contentItem->metadata, 'social_account_id');

        $account = null;

        if ($accountId) {
            $account = SocialAccount::query()
                ->where('brand_id', $contentItem->brand_id)
                ->where('platform', 'youtube')
                ->where('status', 'active')
                ->where('id', $accountId)
                ->with('oauthToken')
                ->first();
        }

        $account ??= SocialAccount::query()
            ->where('brand_id', $contentItem->brand_id)
            ->where('platform', 'youtube')
            ->where('status', 'active')
            ->with('oauthToken')
            ->orderByDesc('connected_at')
            ->first();

        if (! $account?->oauthToken?->access_token) {
            throw new RuntimeException('No YouTube channel is connected. Open Social accounts → YouTube → Connect.');
        }

        return [$account, $account->oauthToken->access_token];
    }

    private function uploadVideoFromAsset(string $token, BrandAsset $asset, string $title, string $description): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            throw new RuntimeException('Video file is missing from storage.');
        }

        $contents = Storage::disk($disk)->get($asset->file_path);
        $mime = $asset->mime_type ?: 'video/mp4';

        return $this->resumableUpload($token, $contents, $mime, $title, $description);
    }

    private function uploadVideoFromUrl(string $token, string $url, string $title, string $description): string
    {
        $response = Http::timeout(120)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download the video file for YouTube upload.');
        }

        return $this->resumableUpload(
            $token,
            $response->body(),
            (string) ($response->header('Content-Type') ?: 'video/mp4'),
            $title,
            $description
        );
    }

    private function resumableUpload(string $token, string $contents, string $mime, string $title, string $description): string
    {
        $init = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-Upload-Content-Type' => $mime,
                'X-Upload-Content-Length' => (string) strlen($contents),
            ])
            ->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', [
                'snippet' => [
                    'title' => mb_substr($title, 0, 100),
                    'description' => mb_substr($description, 0, 5000),
                    'categoryId' => '22',
                ],
                'status' => [
                    'privacyStatus' => 'public',
                    'selfDeclaredMadeForKids' => false,
                ],
            ]);

        if (! $init->successful()) {
            throw new RuntimeException($this->apiError($init, 'Could not start YouTube upload.'));
        }

        $uploadUrl = $init->header('Location');

        if (! $uploadUrl) {
            throw new RuntimeException('YouTube did not return an upload URL.');
        }

        $upload = Http::withToken($token)
            ->withHeaders([
                'Content-Type' => $mime,
                'Content-Length' => (string) strlen($contents),
            ])
            ->withBody($contents, $mime)
            ->put($uploadUrl);

        if (! $upload->successful()) {
            throw new RuntimeException($this->apiError($upload, 'Could not upload video to YouTube.'));
        }

        $videoId = (string) ($upload->json('id') ?: '');

        if ($videoId === '') {
            throw new RuntimeException('YouTube did not return a video ID after upload.');
        }

        return $videoId;
    }

    private function resolveVideoAsset(ContentItem $item): ?BrandAsset
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if (is_string($videoUrl) && $videoUrl !== '') {
            $asset = $this->assetFromUrl($item->brand_id, $videoUrl);

            if ($asset) {
                return $asset;
            }
        }

        $manualKey = data_get($item->metadata, 'visual_manual_key');
        $manualType = data_get($item->metadata, 'visual_manual_type');

        if ($manualType === 'reel' && filled($manualKey) && ctype_digit((string) $manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('id', (int) $manualKey)
                ->first();
        }

        if ($manualType === 'reel' && filled($manualKey)) {
            return BrandAsset::query()
                ->where('brand_id', $item->brand_id)
                ->where('metadata->content_group', $manualKey)
                ->where(function ($query) {
                    $query->where('file_type', 'like', 'video/%')
                        ->orWhere('mime_type', 'like', 'video/%');
                })
                ->first();
        }

        return null;
    }

    private function resolvePublicVideoUrl(ContentItem $item): ?string
    {
        $videoUrl = data_get($item->metadata, 'video_url');

        if (! is_string($videoUrl) || $videoUrl === '' || str_contains($videoUrl, '/assets/')) {
            return null;
        }

        return filter_var($videoUrl, FILTER_VALIDATE_URL) ? $videoUrl : null;
    }

    /** @return list<mixed> */
    private function carouselImageUrls(ContentItem $item): array
    {
        $urls = data_get($item->metadata, 'carousel_images');

        return is_array($urls) ? $urls : [];
    }

    private function assetFromUrl(int $brandId, string $url): ?BrandAsset
    {
        if (! preg_match('#/assets/(\d+)(?:/|\?|$)#', $url, $matches)) {
            return null;
        }

        return BrandAsset::query()
            ->where('brand_id', $brandId)
            ->where('id', (int) $matches[1])
            ->first();
    }

    private function assetPath(BrandAsset $asset): string
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        return Storage::disk($disk)->path($asset->file_path);
    }

    private function downloadToTemp(string $url): string
    {
        $response = Http::timeout(60)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Could not download image for YouTube upload.');
        }

        $tempDir = storage_path('app/temp/youtube');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $extension = str_contains((string) $response->header('Content-Type'), 'png') ? 'png' : 'jpg';
        $path = $tempDir.'/yt-img-'.uniqid('', true).'.'.$extension;
        file_put_contents($path, $response->body());

        return $path;
    }

    private function isImageAsset(BrandAsset $asset): bool
    {
        return str_starts_with((string) $asset->file_type, 'image')
            || str_starts_with((string) ($asset->mime_type ?? ''), 'image/');
    }

    private function isVideoAsset(BrandAsset $asset): bool
    {
        return str_starts_with((string) $asset->file_type, 'video')
            || str_starts_with((string) ($asset->mime_type ?? ''), 'video/');
    }

    private function isPublicHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL)
            && (str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
    }

    private function withShortsTag(string $description): string
    {
        if (str_contains(strtolower($description), '#shorts')) {
            return $description;
        }

        return trim($description."\n\n#Shorts");
    }

    private function withoutShortsTag(string $description): string
    {
        $clean = preg_replace('/#shorts\b/i', '', $description) ?? $description;

        return trim(preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean);
    }

    private function ffmpegBinary(): ?string
    {
        $configured = config('services.youtube.ffmpeg_path');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $local = $this->discoverLocalFfmpeg();

        if ($local !== null) {
            return $local;
        }

        $process = new Process(['ffmpeg', '-version']);
        $process->run();

        return $process->isSuccessful() ? 'ffmpeg' : null;
    }

    private function discoverLocalFfmpeg(): ?string
    {
        $root = base_path('tools/ffmpeg');

        if (! is_dir($root)) {
            return null;
        }

        $names = ['ffmpeg.exe', 'ffmpeg'];

        foreach ($names as $name) {
            $direct = $root.DIRECTORY_SEPARATOR.$name;

            if (is_file($direct)) {
                return $direct;
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $basename = strtolower($file->getBasename());

            if ($basename === 'ffmpeg.exe' || $basename === 'ffmpeg') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function ffmpegAvailable(): bool
    {
        return $this->ffmpegBinary() !== null;
    }

    /** @param  list<string>  $command */
    private function runFfmpeg(array $command): void
    {
        $binary = $this->ffmpegBinary();

        if ($binary === null) {
            throw new RuntimeException(
                'FFmpeg is required to publish image/carousel posts to YouTube. Install ffmpeg on the server, or publish a video file instead.'
            );
        }

        $command[0] = $binary;
        $process = new Process($command);
        $process->setTimeout(300);

        $binDir = dirname($binary);

        if (is_dir($binDir)) {
            $path = $binDir.PATH_SEPARATOR.(getenv('PATH') ?: '');
            $process->setEnv(['PATH' => $path]);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('FFmpeg failed: '.trim($process->getErrorOutput() ?: $process->getOutput()));
        }
    }

    private function apiError(\Illuminate\Http\Client\Response $response, string $fallback): string
    {
        $message = (string) ($response->json('error.message') ?? $response->json('error_description') ?? '');

        if (str_contains($message, 'invalid_grant') || $response->status() === 401) {
            return $fallback.' YouTube session expired. Social Accounts → YouTube → Reconnect.';
        }

        return $message !== '' ? $fallback.' '.$message : $fallback;
    }
}
