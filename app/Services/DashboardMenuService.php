<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardMenuService
{
    public function menus(?string $search, string $category, bool $activeOnly = false, bool $cacheable = true): Collection
    {
        $search = $search ? trim($search) : null;
        $cacheKey = 'dashboard_menus:' . ($activeOnly ? 'active' : 'all') . ':' . $category;

        if ($cacheable && !$search) {
            return Cache::remember($cacheKey, 600, fn () => $this->query($search, $category, $activeOnly)->get());
        }

        return $this->query($search, $category, $activeOnly)->get();
    }

    private function query(?string $search, string $category, bool $activeOnly): Builder
    {
        return Menu::select(['id', 'name'])
            ->whereHas('products', fn ($query) => $this->applyProductFilters($query, $search, $activeOnly))
            ->with([
                'products' => fn ($query) => $this->applyProductFilters($query, $search, $activeOnly)
                    ->select([
                        'id',
                        'menu_id',
                        'name',
                        'description',
                        'price',
                        'price_cents',
                        'image',
                        'image_thumb',
                        'image_detail',
                        'oz_redeem_value',
                        'is_active',
                        'created_at',
                    ])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->orderBy('name'),
            ])
            ->withCount([
                'products as products_count' => fn ($query) => $this->applyProductFilters($query, $search, $activeOnly),
            ])
            ->when($category !== 'all', fn ($query) => $query->where('name', $category))
            ->orderBy('name');
    }

    private function applyProductFilters($query, ?string $search, bool $activeOnly)
    {
        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        return $query;
    }
}
