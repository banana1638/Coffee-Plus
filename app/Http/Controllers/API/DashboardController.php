<?php

namespace App\Http\Controllers\Api;

use \App\Models\Menu;
use \Illuminate\Http\Request;
use \App\Http\Controllers\Controller;
use \App\Http\Resources\Api\CategoryResource;
use \App\Http\Resources\Api\UserResource;
use \Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $category = $request->input('category', 'all');

        $menus = Menu::whereHas('products', function ($query) use ($search) {
            if ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            }
        })
            ->with([
                'products' => function ($query) use ($search) {
                    if ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    }
                }
            ])
            ->when($category !== 'all', function ($query) use ($category) {
                return $query->where('name', $category);
            })
            ->get();

        $allCategoryNames = Cache::remember('menu_category_names', 3600, function () {
            return Menu::pluck('name');
        });

        return response()->json([
            'menus' => CategoryResource::collection($menus),
            'allCategoryNames' => $allCategoryNames,
            'search' => $search,
            'category' => $category,
            'user' => new UserResource($request->user()),
        ]);
    }
}
