<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Templates\FontCatalog;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the self-hosted webfonts, and the manifest describing them.
 *
 * The preview iframe needs the same @font-face rules an export bundles, so the
 * families and their faces are published here and the renderer builds the CSS
 * from them on both paths.
 */
final class FontController extends Controller
{
    public function index(FontCatalog $fonts): JsonResponse
    {
        return response()->json([
            'data' => [
                'families' => array_values($fonts->all()),
                'faces' => $fonts->manifest(),
            ],
        ]);
    }

    /**
     * Only files listed in the generated manifest resolve, so the filename in
     * the URL can never be used to read anything else off disk.
     */
    public function show(string $file, FontCatalog $fonts): BinaryFileResponse
    {
        $path = $fonts->filePath($file);

        abort_if($path === null || ! is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'font/woff2',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
