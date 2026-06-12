<?php

namespace App\Http\Controllers\API;

use App\Models\Menu;
use App\Services\DashboardMenuService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CategoryResource;
use App\Http\Resources\Api\UserResource;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardMenuService $dashboardMenuService)
    {
        $search = $request->input('search');
        $category = $request->input('category', 'all');

        $menus = $dashboardMenuService->menus($search, $category, true);

        $allCategoryNames = Cache::remember('menu_category_names', 3600, function () {
            return Menu::orderBy('name')->pluck('name');
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
