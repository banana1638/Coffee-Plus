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

        $coffeeConfig = config('coffee.options');

        $sizeExtra = collect($coffeeConfig['sizes'])
            ->firstWhere('name', $request->size)['extra'] ?? 0;

        $selectedAddons = $request->input('addons', []);

        $addonsTotal = $product->addons()
            ->whereIn('name', $selectedAddons)
            ->sum('price');

        $finalUnitPrice = $product->price + $sizeExtra + $addonsTotal;

        $addonsArray = $request->input('addons', []);
        sort($addonsArray);

        /** @var \App\Models\CartItem|null $cartItem */
        $cartItem = CartItem::where('user_id', Auth::id())
            ->where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->where('temp', $request->temp)
            ->get()
            ->first(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->save();
        } else {
            $cartItem = new CartItem();
            $cartItem->user_id = Auth::id();
            $cartItem->product_id = $request->product_id;
            $cartItem->quantity = $request->quantity;
            $cartItem->size = $request->size;
            $cartItem->temp = $request->temp;
            $cartItem->addons = $addonsArray;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->save();
        }

        $cartCount = CartItem::where('user_id', Auth::id())->sum('quantity');

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
            'quantity' => 'required|integer|min:1',
        ]);

        if ($request->has('cart_item_id')) {
            $request->validate(['cart_item_id' => 'exists:cart_items,id']);
            CartItem::where('user_id', Auth::id())
                ->where('id', $request->cart_item_id)
                ->update(['quantity' => $request->quantity]);
        } else {
            // Fallback for old clients
            $request->validate(['product_id' => 'required|exists:products,id']);
            CartItem::where('user_id', Auth::id())
                ->where('product_id', $request->product_id)
                ->update(['quantity' => $request->quantity]);
        }

        return Response::json([
            'status' => 'success',
            'message' => 'Cart updated!'
        ]);
    }

    public function destroy(Request $request)
    {
        if ($request->has('cart_item_id')) {
            $request->validate(['cart_item_id' => 'exists:cart_items,id']);
            CartItem::where('user_id', Auth::id())
                ->where('id', $request->cart_item_id)
                ->delete();
        } else {
            $request->validate(['product_id' => 'required|exists:products,id']);
            CartItem::where('user_id', Auth::id())
                ->where('product_id', $request->product_id)
                ->delete();
        }

        return Response::json([
            'status' => 'success',
            'message' => 'Item removed!'
        ]);
    }
}