<?php

namespace App\Actions;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\PublicMediaUrl;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessAndStoreProductImage
{
    public const MAX_IMAGES_PER_PRODUCT = 8;

    public const MAX_UPLOAD_KB = 10240;

    public const MIN_DIMENSION = 600;

    public const CANONICAL_SIZE = 2048;

    /**
     * @return array{id: int, path: string, url: string|null, alt_text: string|null, is_primary: bool, sort_order: int}
     */
    public function handle(
        Product $product,
        UploadedFile $file,
        ?string $altText = null,
        ?bool $makePrimary = null,
    ): array {
        if ($product->images()->count() >= self::MAX_IMAGES_PER_PRODUCT) {
            throw ValidationException::withMessages([
                'image' => 'A product may have at most '.self::MAX_IMAGES_PER_PRODUCT.' images.',
            ]);
        }

        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false) {
            throw ValidationException::withMessages([
                'image' => 'Unable to read image dimensions. Upload a valid JPG, PNG, or WEBP file.',
            ]);
        }

        [$width, $height, $type] = $dimensions;

        if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
            throw ValidationException::withMessages([
                'image' => sprintf(
                    'Image resolution is too low. Minimum size is %d×%d pixels.',
                    self::MIN_DIMENSION,
                    self::MIN_DIMENSION,
                ),
            ]);
        }

        try {
            $binary = $this->centerCropToSquare($file->getRealPath(), (int) $type);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Product image processing failed', [
                'product_id' => $product->id,
                'exception' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'image' => 'Image processing is temporarily unavailable. Please try again.',
            ]);
        }

        $filename = 'product-'.Str::uuid()->toString().'.jpg';
        $path = 'products/'.$product->id.'/'.$filename;

        Storage::disk('public')->put($path, $binary);

        $shouldBePrimary = $makePrimary === true
            || ($makePrimary === null && ! $product->images()->where('is_primary', true)->exists());

        $image = DB::transaction(function () use ($product, $path, $altText, $shouldBePrimary): ProductImage {
            if ($shouldBePrimary) {
                $product->images()->update(['is_primary' => false]);
            }

            $sortOrder = (int) ($product->images()->max('sort_order') ?? -1) + 1;

            return $product->images()->create([
                'path' => $path,
                'alt_text' => $altText ?: $product->name,
                'is_primary' => $shouldBePrimary,
                'sort_order' => $sortOrder,
            ]);
        });

        return $this->serialize($image);
    }

    public function setPrimary(Product $product, ProductImage $image): ProductImage
    {
        abort_unless($image->product_id === $product->id, 404);

        return DB::transaction(function () use ($product, $image): ProductImage {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);

            return $image->fresh();
        });
    }

    public function delete(Product $product, ProductImage $image): void
    {
        abort_unless($image->product_id === $product->id, 404);

        DB::transaction(function () use ($product, $image): void {
            $wasPrimary = $image->is_primary;
            $path = $image->path;
            $image->delete();

            if ($wasPrimary) {
                $next = $product->images()->orderBy('sort_order')->orderBy('id')->first();
                if ($next !== null) {
                    $next->update(['is_primary' => true]);
                }
            }

            if ($path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    /**
     * @return array{id: int, path: string, url: string|null, alt_text: string|null, is_primary: bool, sort_order: int}
     */
    public function serialize(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'path' => $image->path,
            'url' => PublicMediaUrl::fromPath($image->path),
            'alt_text' => $image->alt_text,
            'is_primary' => $image->is_primary,
            'sort_order' => $image->sort_order,
        ];
    }

    private function centerCropToSquare(string $sourcePath, int $imageType): string
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            Log::error('Product image processing unavailable: GD extension is not loaded', [
                'php_sapi' => PHP_SAPI,
                'php_version' => PHP_VERSION,
                'ini' => php_ini_loaded_file(),
            ]);

            throw ValidationException::withMessages([
                'image' => 'Image processing is unavailable on this server. Ensure the PHP GD extension is enabled and restart php artisan serve.',
            ]);
        }

        $source = $this->createImageResource($sourcePath, $imageType);

        if ($source === false) {
            throw ValidationException::withMessages([
                'image' => 'Unable to process this image. Use JPG, PNG, or WEBP.',
            ]);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $srcX = (int) floor(($width - $side) / 2);
        $srcY = (int) floor(($height - $side) / 2);
        $target = min(self::CANONICAL_SIZE, $side);

        $canvas = imagecreatetruecolor($target, $target);
        if ($canvas === false) {
            imagedestroy($source);
            throw ValidationException::withMessages([
                'image' => 'Unable to create square image canvas.',
            ]);
        }

        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $target, $target, $white);

        $copied = imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $target,
            $target,
            $side,
            $side,
        );

        imagedestroy($source);

        if (! $copied) {
            imagedestroy($canvas);
            throw ValidationException::withMessages([
                'image' => 'Unable to crop image to square.',
            ]);
        }

        ob_start();
        imagejpeg($canvas, null, 90);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        if ($binary === '') {
            throw ValidationException::withMessages([
                'image' => 'Unable to encode processed image.',
            ]);
        }

        return $binary;
    }

    private function createImageResource(string $sourcePath, int $imageType): GdImage|false
    {
        $bytes = @file_get_contents($sourcePath);

        if ($bytes !== false && $bytes !== '') {
            $fromString = @imagecreatefromstring($bytes);
            if ($fromString !== false) {
                return $fromString;
            }
        }

        return match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp')
                ? @imagecreatefromwebp($sourcePath)
                : false,
            default => false,
        };
    }
}
