<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Menu;
use App\Services\AuditLogService;
use App\Services\ProductImageService;
use Illuminate\Http\Request;

class ProductAdminController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProductImageService $productImageService
    )
    {
    }

    public function index() {
        $products = Product::with('menu')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(20);

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
            'track_stock' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0|required_if:track_stock,1',
            'image' => 'nullable|file|image|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp|max:5120',
        ]);

        $product = new Product();
        $product->name = $request->name;
        $product->price = $request->price;
        $product->menu_id = $request->menu_id;
        $product->oz_redeem_value = $request->oz_redeem_value ?? 0;
        $product->track_stock = $request->boolean('track_stock');
        $product->stock = $product->track_stock ? (int) $request->input('stock', 0) : null;

        if ($request->hasFile('image')) {
            $product->forceFill($this->productImageService->store($request->file('image')));
        }

        $product->save();
        $this->auditLogService->record('product.create', $product, null, $product->only([
            'name',
            'price',
            'menu_id',
            'oz_redeem_value',
            'track_stock',
            'stock',
            'image',
            'image_thumb',
            'image_detail',
        ]));

        if ($request->has('addons') && is_array($request->addons)) {
            foreach ($request->addons as $addonData) {
                if (!empty($addonData['name']) && isset($addonData['price'])) {
                    $addon = new \App\Models\ProductAddon();
                    $addon->product_id = $product->id;
                    $addon->name = $addonData['name'];
                    $addon->price = $addonData['price'];
                    $addon->save();
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created!');
    }

    public function edit($id) {
        $product = Product::findOrFail($id);
        $menus = Menu::all();
        return view('admin.products.edit', compact('product', 'menus'));
    }

    public function update(Request $request, $id) {
        $product = Product::findOrFail($id);
        $oldValues = $product->only([
            'name',
            'price',
            'menu_id',
            'oz_redeem_value',
            'track_stock',
            'stock',
            'image',
            'image_thumb',
            'image_detail',
        ]);
        
        $request->validate([
            'name' => 'required|max:255', 
            'price' => 'required|numeric',
            'menu_id' => 'required',
            'oz_redeem_value' => 'nullable|numeric',
            'track_stock' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0|required_if:track_stock,1',
            'image' => 'nullable|file|image|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp|max:5120',
        ]);

        $product->name = $request->name;
        $product->price = $request->price;
        $product->menu_id = $request->menu_id;
        $product->oz_redeem_value = $request->oz_redeem_value;
        $product->track_stock = $request->boolean('track_stock');
        $product->stock = $product->track_stock ? (int) $request->input('stock', 0) : null;
        $oldImage = null;
        $oldThumb = null;
        $oldDetail = null;

        if ($request->hasFile('image')) {
            $oldImage = $product->image;
            $oldThumb = $product->image_thumb;
            $oldDetail = $product->image_detail;

            $product->forceFill($this->productImageService->store($request->file('image')));
        }

        $product->save();

        if ($request->hasFile('image')) {
            $this->productImageService->delete($oldImage, $oldThumb, $oldDetail);
        }

        $this->auditLogService->record('product.update', $product, $oldValues, $product->only([
            'name',
            'price',
            'menu_id',
            'oz_redeem_value',
            'track_stock',
            'stock',
            'image',
            'image_thumb',
            'image_detail',
        ]));

        if ($request->has('addons') && is_array($request->addons)) {
            $product->addons()->delete();
            foreach ($request->addons as $addonData) {
                if (!empty($addonData['name']) && isset($addonData['price'])) {
                    $addon = new \App\Models\ProductAddon();
                    $addon->product_id = $product->id;
                    $addon->name = $addonData['name'];
                    $addon->price = $addonData['price'];
                    $addon->save();
                }
            }
        } else {
            $product->addons()->delete();
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated!');
    }

    public function destroy($id) {
        $product = Product::findOrFail($id);
        $oldValues = $product->only([
            'name',
            'price',
            'menu_id',
            'oz_redeem_value',
            'track_stock',
            'stock',
            'image',
            'image_thumb',
            'image_detail',
        ]);

        $this->productImageService->delete($product->image, $product->image_thumb, $product->image_detail);

        $product->delete();
        $this->auditLogService->record('product.delete', $product, $oldValues, null);
        return back()->with('success', 'Product deleted!');
    }
}
