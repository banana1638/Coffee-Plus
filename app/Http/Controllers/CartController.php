<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return response()->json([
            'status' => 'success',
            'cartCount' => $cartCount,
            'message' => 'Added to cart successfully!'
        ]);
    }

    public function index()
    {
        $cartItems = CartItem::with('product')->where('user_id', Auth::id())->get();
        return view('user.cart.index', compact('cartItems'));
    }
}