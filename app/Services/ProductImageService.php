<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductImageService
{
    private const BASE_DIRECTORY = 'images/products';
    private const THUMB_DIRECTORY = 'images/products/optimized/thumbs';
    private const DETAIL_DIRECTORY = 'images/products/optimized/detail';

    public function store(UploadedFile $image): array
    {
        File::ensureDirectoryExists(public_path(self::BASE_DIRECTORY));

        $filename = (string) Str::uuid() . '.' . $image->extension();
        $image->move(public_path(self::BASE_DIRECTORY), $filename);

        $originalPath = public_path(self::BASE_DIRECTORY . '/' . $filename);

        return [
            'image' => $filename,
            'image_thumb' => $this->createVariant($originalPath, self::THUMB_DIRECTORY, 420, 82),
            'image_detail' => $this->createVariant($originalPath, self::DETAIL_DIRECTORY, 960, 86),
        ];
    }

    public function backfill(Product $product): array
    {
        if (!$product->image) {
            return [
                'image_thumb' => null,
                'image_detail' => null,
            ];
        }

        $originalPath = public_path(self::BASE_DIRECTORY . '/' . $product->image);

        return [
            'image_thumb' => $product->image_thumb ?: $this->createVariant($originalPath, self::THUMB_DIRECTORY, 420, 82),
            'image_detail' => $product->image_detail ?: $this->createVariant($originalPath, self::DETAIL_DIRECTORY, 960, 86),
        ];
    }

    public function delete(?string $image, ?string $thumb = null, ?string $detail = null): void
    {
        foreach (array_filter([$image, $thumb, $detail]) as $filename) {
            $path = public_path(self::BASE_DIRECTORY . '/' . $filename);

            if (!File::exists($path)) {
                $path = public_path($filename);
            }

            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }

    private function createVariant(string $sourcePath, string $directory, int $size, int $quality): ?string
    {
        if (!File::exists($sourcePath) || !function_exists('imagewebp')) {
            return null;
        }

        $source = @imagecreatefromstring((string) File::get($sourcePath));
        if (!$source) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = (int) floor(($sourceWidth - $cropSize) / 2);
        $cropY = (int) floor(($sourceHeight - $cropSize) / 2);

        $variant = imagecreatetruecolor($size, $size);
        imagealphablending($variant, false);
        imagesavealpha($variant, true);

        imagecopyresampled(
            $variant,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $size,
            $size,
            $cropSize,
            $cropSize
        );

        File::ensureDirectoryExists(public_path($directory));

        $filename = (string) Str::uuid() . '.webp';
        $relativePath = $directory . '/' . $filename;

        $saved = imagewebp($variant, public_path($relativePath), $quality);

        imagedestroy($variant);
        imagedestroy($source);

        return $saved ? $relativePath : null;
    }
}
