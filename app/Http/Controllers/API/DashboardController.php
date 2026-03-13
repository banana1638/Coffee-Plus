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

        $menus = Menu::with([
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
            // 修改这里：如果没登录，返回一个具有默认结构的对象
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
