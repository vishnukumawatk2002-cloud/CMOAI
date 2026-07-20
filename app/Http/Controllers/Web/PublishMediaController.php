<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BrandAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublishMediaController extends Controller
{
    public function show(BrandAsset $asset): StreamedResponse
    {
        $disk = $asset->disk === 'public' ? 'public' : 'local';

        if (! Storage::disk($disk)->exists($asset->file_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            $asset->file_path,
            $asset->file_name,
            ['Content-Type' => $asset->mime_type ?: 'application/octet-stream']
        );
    }
}
