<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CartResource;
use App\Contracts\CartServiceInterface;
use App\Http\Requests\API\AddCartItemRequest;
use App\Http\Requests\API\UpdateCartItemRequest;
use App\Http\Requests\API\RemoveCartItemRequest;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    use ApiResponse;

    protected CartServiceInterface $cartService;

    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    public function add(AddCartItemRequest $request)
    {
        $this->cartService->add(
            Auth::user(),
            (int) $request->product_id,
            (int) $request->quantity,
            $request->size,
            $request->temp,
            $request->input('addons', []) ?? []
        );

        $cartCount = $this->cartService->getCartCount(Auth::user());

        return $this->success([
            'cartCount' => $cartCount
        ], 'Added to cart successfully!');
    }

    public function index()
    {
        $cartItems = $this->cartService->getCartItems(Auth::user());

        return $this->success([
            'cartItems' => CartResource::collection($cartItems)
        ]);
    }

    public function update(UpdateCartItemRequest $request)
    {
        $cartItemId = $request->has('cart_item_id') ? (int) $request->cart_item_id : null;
        $productId = $request->has('product_id') ? (int) $request->product_id : null;

        $this->cartService->updateQuantity(Auth::user(), $cartItemId, $productId, (int) $request->quantity);

        return $this->success(null, 'Cart updated!');
    }

    public function destroy(RemoveCartItemRequest $request)
    {
        $cartItemId = $request->has('cart_item_id') ? (int) $request->cart_item_id : null;
        $productId = $request->has('product_id') ? (int) $request->product_id : null;

        $this->cartService->removeItem(Auth::user(), $cartItemId, $productId);

        return $this->success(null, 'Item removed!');
    }
}