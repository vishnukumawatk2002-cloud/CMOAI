<?php

namespace App\Application\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageOptimizer
{
    public function storeOptimized(
        UploadedFile $file,
        string $directory = 'uploads',
        int $maxWidth = 800,
        int $maxHeight = 800,
        int $quality = 85,
    ): string {
        $this->validateImage($file);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid().'.'.$extension;
        $path = trim($directory, '/').'/'.$filename;

        if (! extension_loaded('gd')) {
            return $file->storeAs($directory, $filename, 'public');
        }

        $source = $this->createImageResource($file->getRealPath(), $file->getMimeType());
        [$width, $height] = $this->scaledDimensions(imagesx($source), imagesy($source), $maxWidth, $maxHeight);

        $canvas = imagecreatetruecolor($width, $height);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

        $tempPath = sys_get_temp_dir().'/'.$filename;
        $this->saveImage($canvas, $tempPath, $extension, $quality);

        imagedestroy($source);
        imagedestroy($canvas);

        Storage::disk('public')->put($path, file_get_contents($tempPath));
        @unlink($tempPath);

        return $path;
    }

    private function validateImage(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('Invalid image type.');
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new RuntimeException('Image exceeds 5MB limit.');
        }
    }

    private function createImageResource(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            'image/gif' => imagecreatefromgif($path),
            default => throw new RuntimeException('Unsupported image format.'),
        };
    }

    private function scaledDimensions(int $width, int $height, int $maxWidth, int $maxHeight): array
    {
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

        return [(int) round($width * $ratio), (int) round($height * $ratio)];
    }

    private function saveImage($resource, string $path, string $extension, int $quality): void
    {
        match ($extension) {
            'png' => imagepng($resource, $path, (int) round(9 - ($quality / 10))),
            'webp' => imagewebp($resource, $path, $quality),
            'gif' => imagegif($resource, $path),
            default => imagejpeg($resource, $path, $quality),
        };
    }
}
