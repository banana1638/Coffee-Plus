<?php

namespace App\Http\Controllers\Api;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\UserResource;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category', 'all');

        $query = Menu::whereHas('products', function ($q) use ($search) {
            $q->where('is_active', true);
            if ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            }
        })
        ->with([
            'products' => function ($q) use ($search) {
                $q->where('is_active', true);
                if ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                }
            }
        ])
        ->when($category !== 'all', function ($q) use ($category) {
            return $q->where('name', $category);
        });

        if (empty($search)) {
            $cacheKey = 'dashboard_menus_' . $category;
            $menus = Cache::remember($cacheKey, 600, function () use ($query) {
                return $query->get();
            });
        } else {
            $menus = $query->get();
        }

        $allCategoryNames = Cache::remember('menu_category_names', 3600, function () {
            return Menu::pluck('name');
        });

        return response()->json([
            'menus' => CategoryResource::collection($menus),
            'allCategoryNames' => $allCategoryNames,
            'options' => config('coffee.options'),
            'search' => $search,
            'category' => $category,
            'user' => $request->user('sanctum')
                ? new UserResource($request->user('sanctum'))
                : [
                    'id' => null,
                    'name' => 'Guest',
                    'email' => '',
                    'balance' => 0,
                    'oz' => 0
                ],
        ]);
    }
}
