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

        $allCategoryNames = \Illuminate\Support\Facades\Cache::remember('menu_category_names', 3600, function () {
            return Menu::pluck('name');
        });

        return view('dashboard', compact('menus', 'allCategoryNames', 'search', 'category'));
    }
}
