<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
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
                ->get();
            
            $menus = collect(); // We don't use regular menus for collections view
        } else {
            $favorites = collect();
            $menus = Menu::whereHas('products', function ($query) use ($search) {
                if ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }
            })
                ->with(['products' => function ($query) use ($search) {
                if ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                }
            }])
                ->when($category !== 'all', function ($query) use ($category) {
                return $query->where('name', $category);
            })
                ->get();
        }

        $allCategoryNames = \Illuminate\Support\Facades\Cache::remember('menu_category_names', 3600, function () {
            return Menu::pluck('name');
        });

        return view('user.dashboard', compact('menus', 'favorites', 'allCategoryNames', 'search', 'category'));
    }
}
