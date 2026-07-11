<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Services\AuditLogService;
use App\Services\ProductImageService;
use Illuminate\Http\Request;

class ProductAdminController extends Controller
{
    private const AUDIT_FIELDS = [
        'name',
        'price',
        'menu_id',
        'oz_redeem_value',
        'track_stock',
        'stock',
        'image',
        'image_thumb',
        'image_detail',
    ];

    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ProductImageService $productImageService
    ) {
    }

    public function index()
    {
        $products = Product::with('menu')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $menus = Menu::select(['id', 'name'])->orderBy('name')->get();

        return view('admin.products.create', compact('menus'));
    }

    public function store(Request $request)
    {
        $product = new Product();

        $this->fillProductFromRequest($product, $request, defaultOzRedeemValue: 0);

        if ($request->hasFile('image')) {
            $product->forceFill($this->productImageService->store($request->file('image')));
        }

        $product->save();
        $this->auditLogService->record('product.create', $product, null, $product->only(self::AUDIT_FIELDS));
        $this->syncAddons($product, $request->input('addons', []), deleteMissing: false);

        return redirect()->route('admin.products.index')->with('success', 'Product created!');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $menus = Menu::select(['id', 'name'])->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'menus'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $oldValues = $product->only(self::AUDIT_FIELDS);

        $this->fillProductFromRequest($product, $request);

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

        $this->auditLogService->record('product.update', $product, $oldValues, $product->only(self::AUDIT_FIELDS));
        $this->syncAddons($product, $request->input('addons', []), deleteMissing: true);

        return redirect()->route('admin.products.index')->with('success', 'Product updated!');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $oldValues = $product->only(self::AUDIT_FIELDS);

        $this->productImageService->delete($product->image, $product->image_thumb, $product->image_detail);

        $product->delete();
        $this->auditLogService->record('product.delete', $product, $oldValues, null);

        return back()->with('success', 'Product deleted!');
    }

    private function fillProductFromRequest(Product $product, Request $request, ?int $defaultOzRedeemValue = null): void
    {
        $request->validate([
            'name' => 'required|max:255',
            'price' => 'required|numeric|min:0.01|max:10000|decimal:0,2',
            'menu_id' => 'required|integer|exists:menus,id',
            'oz_redeem_value' => 'nullable|integer|min:0|max:1000000',
            'track_stock' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0|required_if:track_stock,1',
            'image' => 'nullable|file|image|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp|max:5120',
            'addons' => 'nullable|array|max:20',
            'addons.*.name' => 'required|string|max:100',
            'addons.*.price' => 'required|numeric|min:0|max:10000|decimal:0,2',
        ]);

        $product->name = $request->name;
        $product->price = $request->price;
        $product->menu_id = $request->menu_id;
        $product->oz_redeem_value = $request->oz_redeem_value ?? $defaultOzRedeemValue;
        $product->track_stock = $request->boolean('track_stock');
        $product->stock = $product->track_stock ? (int) $request->input('stock', 0) : null;
    }

    private function syncAddons(Product $product, mixed $addons, bool $deleteMissing): void
    {
        if ($deleteMissing) {
            $product->addons()->delete();
        }

        if (!is_array($addons)) {
            return;
        }

        foreach ($addons as $addonData) {
            if (empty($addonData['name']) || !isset($addonData['price'])) {
                continue;
            }

            $addon = new ProductAddon();
            $addon->product_id = $product->id;
            $addon->name = $addonData['name'];
            $addon->price = $addonData['price'];
            $addon->save();
        }
    }
}
