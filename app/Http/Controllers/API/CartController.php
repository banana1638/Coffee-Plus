<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\Api\CartResource;
use App\Contracts\CartServiceInterface;
use Illuminate\Support\Facades\Response;

class CartController extends Controller
{
    protected CartServiceInterface $cartService;

    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'size' => 'required',
            'temp' => 'required',
            'addons' => 'nullable|array',
        ]);

        $this->cartService->add(
            Auth::user(),
            (int) $request->product_id,
            (int) $request->quantity,
            $request->size,
            $request->temp,
            $request->input('addons', []) ?? []
        );

        $cartCount = $this->cartService->getCartCount(Auth::user());

        return Response::json([
            'status' => 'success',
            'cartCount' => $cartCount,
            'message' => 'Added to cart successfully!'
        ]);
    }

    public function index()
    {
        $cartItems = $this->cartService->getCartItems(Auth::user());

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

        $cartItemId = null;
        $productId = null;

        if ($request->has('cart_item_id')) {
            $request->validate(['cart_item_id' => 'exists:cart_items,id']);
            $cartItemId = (int) $request->cart_item_id;
        } else {
            $request->validate(['product_id' => 'required|exists:products,id']);
            $productId = (int) $request->product_id;
        }

        $this->cartService->updateQuantity(Auth::user(), $cartItemId, $productId, (int) $request->quantity);

        return Response::json([
            'status' => 'success',
            'message' => 'Cart updated!'
        ]);
    }

    public function destroy(Request $request)
    {
        $cartItemId = null;
        $productId = null;

        if ($request->has('cart_item_id')) {
            $request->validate(['cart_item_id' => 'exists:cart_items,id']);
            $cartItemId = (int) $request->cart_item_id;
        } else {
            $request->validate(['product_id' => 'required|exists:products,id']);
            $productId = (int) $request->product_id;
        }

        $this->cartService->removeItem(Auth::user(), $cartItemId, $productId);

        return Response::json([
            'status' => 'success',
            'message' => 'Item removed!'
        ]);
    }
}