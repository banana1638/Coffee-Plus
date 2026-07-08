<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\DashboardMenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardMenuService $dashboardMenuService)
    {
        $search = $request->input('search');
        $category = $request->input('category', 'all');

        if ($category === 'collections' && auth()->check()) {
            $favorites = auth()->user()->favorites()
                ->with('product')
                ->whereHas('product', function ($query) use ($search) {
                    if ($search) {
                        $query->where('name', 'like', '%' . $search . '%');
                    }
                })
                ->latest()
                ->paginate(12)
                ->withQueryString();
            
            $menus = collect(); // We don't use regular menus for collections view
        } else {
            $favorites = collect();
            $menus = $dashboardMenuService->menus($search, $category);
        }

        $allCategoryNames = Cache::remember('menu_category_names', 3600, function () {
            return Menu::orderBy('name')->pluck('name');
        });

        return view('user.dashboard', compact('menus', 'favorites', 'allCategoryNames', 'search', 'category'));
    }
}
