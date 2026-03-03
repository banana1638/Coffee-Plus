<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductAdminController extends Controller
{
    public function index() {
        $products = Product::with('menu')->latest()->get();
        return view('admin.products.index', compact('products'));
    }

    public function create() {
        $menus = Menu::all(); 
        return view('admin.products.create', compact('menus'));
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required|max:255',
            'price' => 'required|numeric',
            'menu_id' => 'required',
            'oz_redeem_value' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->price = $request->price;
        $product->menu_id = $request->menu_id;
        $product->oz_redeem_value = $request->oz_redeem_value ?? 0;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/products'), $filename);
            $product->image = $filename;
        }

        $product->save();
        return redirect()->route('admin.products.index')->with('success', 'Product created!');
    }

    public function edit($id) {
        $product = Product::findOrFail($id);
        $menus = Menu::all();
        return view('admin.products.edit', compact('product', 'menus'));
    }

    public function update(Request $request, $id) {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name' => 'required|max:255', 
            'price' => 'required|numeric',
            'menu_id' => 'required',
            'oz_redeem_value' => 'nullable|numeric'
        ]);

        $product->name = $request->name;
        $product->price = $request->price;
        $product->menu_id = $request->menu_id;
        $product->oz_redeem_value = $request->oz_redeem_value;

        if ($request->hasFile('image')) {
            if ($product->image && File::exists(public_path('images/products/'.$product->image))) {
                File::delete(public_path('images/products/'.$product->image));
            }
            
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $image->move(public_path('images/products'), $filename);
            $product->image = $filename;
        }

        $product->save();
        return redirect()->route('admin.products.index')->with('success', 'Product updated!');
    }

    public function destroy($id) {
        $product = Product::findOrFail($id);

        if ($product->image && File::exists(public_path('images/products/'.$product->image))) {
            File::delete(public_path('images/products/'.$product->image));
        }

        $product->delete();
        return back()->with('success', 'Product deleted!');
    }
}