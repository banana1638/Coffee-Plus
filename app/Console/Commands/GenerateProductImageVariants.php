<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Console\Command;

class GenerateProductImageVariants extends Command
{
    protected $signature = 'products:generate-image-variants';

    protected $description = 'Generate optimized product image variants while keeping original images as fallback.';

    public function handle(ProductImageService $productImageService): int
    {
        $processed = 0;

        Product::whereNotNull('image')
            ->where(function ($query) {
                $query->whereNull('image_thumb')
                    ->orWhereNull('image_detail');
            })
            ->chunkById(50, function ($products) use ($productImageService, &$processed) {
                foreach ($products as $product) {
                    $variants = $productImageService->backfill($product);

                    $product->forceFill($variants)->save();
                    $processed++;
                }
            });

        $this->info("Generated image variants for {$processed} products.");

        return self::SUCCESS;
    }
}
