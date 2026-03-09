<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\CartResource;
use Illuminate\Support\Facades\Response;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'size' => 'required',
            'temp' => 'required',
            'addons' => 'nullable|array',
        ]);

        $product = Product::findOrFail($request->product_id);

        // 获取配置
        $coffeeConfig = config('coffee.options');

        $sizeExtra = collect($coffeeConfig['sizes'])
            ->firstWhere('name', $request->size)['extra'] ?? 0;

        $selectedAddons = $request->input('addons', []);
        $addonsTotal = collect($coffeeConfig['add_ons'])
            ->whereIn('name', $selectedAddons)
            ->sum('price');

        $finalUnitPrice = $product->price + $sizeExtra + $addonsTotal;

        $cartItem = new CartItem();
        $cartItem->user_id = Auth::id();
        $cartItem->product_id = $request->product_id;
        $cartItem->quantity = $request->quantity;
        $cartItem->size = $request->size;
        $cartItem->temp = $request->temp;
        $cartItem->addons = $request->input('addons', []);
        $cartItem->unit_price = $finalUnitPrice;
        $cartItem->save();

        $cartCount = CartItem::where('user_id', Auth::id())->sum('quantity');

        // 使用 Response Facade
        return Response::json([
            'status' => 'success',
            'cartCount' => $cartCount,
            'message' => 'Added to cart successfully!'
        ]);
    }

    public function index()
    {
        $cartItems = CartItem::with('product')->where('user_id', Auth::id())->get();

        return Response::json([
            'status' => 'success',
            'cartItems' => CartResource::collection($cartItems)
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        CartItem::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->update(['quantity' => $request->quantity]);

        return Response::json([
            'status' => 'success',
            'message' => 'Cart updated!'
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        CartItem::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->delete();

        return Response::json([
            'status' => 'success',
            'message' => 'Item removed!'
        ]);
    }
}