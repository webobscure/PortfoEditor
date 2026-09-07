<?php

declare(strict_types=1);

namespace App\Services\Media;

use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Normalises an uploaded image.
 *
 * Re-encoding is a security measure as much as a size one: decoding the pixels
 * and writing a fresh file discards EXIF, trailing data and anything else
 * hidden in the original container, so what lands on disk is only ever an
 * image this application produced.
 */
final class ImageProcessor
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * @return array{contents:string,mime:string,extension:string,width:int,height:int}
     */
    public function process(string $sourcePath): array
    {
        // Intervention Image 4.3 exposes decodePath(); read() was removed.
        $image = $this->manager->decodePath($sourcePath);

        $max = (int) config('portfolio.uploads.max_dimension');

        if ($image->width() > $max || $image->height() > $max) {
            $image->scaleDown(width: $max, height: $max);
        }

        $quality = (int) config('portfolio.uploads.quality');

        if (config('portfolio.uploads.convert_to_webp')) {
            $encoded = $image->encode(new WebpEncoder(quality: $quality));
            $mime = 'image/webp';
            $extension = 'webp';
        } else {
            $encoded = $image->encode(new JpegEncoder(quality: $quality));
            $mime = 'image/jpeg';
            $extension = 'jpg';
        }

        return [
            'contents' => (string) $encoded,
            'mime' => $mime,
            'extension' => $extension,
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }
}
