<?php

namespace App\Http\Controllers;

use App\Contracts\CartServiceInterface;
use App\Http\Requests\API\AddCartItemRequest;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
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

        return response()->json([
            'status' => 'success',
            'cartCount' => $cartCount,
            'message' => 'Added to cart successfully!'
        ]);
    }

    public function index()
    {
        $cartItems = $this->cartService->getCartItems(Auth::user());
        return view('user.cart.index', compact('cartItems'));
    }
}
