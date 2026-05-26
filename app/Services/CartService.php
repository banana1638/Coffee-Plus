<?php

namespace App\Services;

use App\Contracts\CartServiceInterface;
use App\Contracts\PricingServiceInterface;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;

class CartService implements CartServiceInterface
{
    protected PricingServiceInterface $pricingService;

    public function __construct(PricingServiceInterface $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Add or update an item in the user's cart.
     */
    public function add(User $user, int $productId, int $quantity, string $size, string $temp, array $addons): CartItem
    {
        $product = Product::findOrFail($productId);
        $finalUnitPrice = $this->pricingService->calculateUnitPrice($product, $size, $addons);

        $addonsArray = $addons;
        sort($addonsArray);

        /** @var CartItem|null $cartItem */
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->get()
            ->first(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });

        if ($cartItem) {
            $cartItem->quantity += $quantity;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->save();
        } else {
            $cartItem = new CartItem();
            $cartItem->user_id = $user->id;
            $cartItem->product_id = $productId;
            $cartItem->quantity = $quantity;
            $cartItem->size = $size;
            $cartItem->temp = $temp;
            $cartItem->addons = $addonsArray;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->save();
        }

        return $cartItem;
    }

    /**
     * Get all cart items with product relation for the user.
     */
    public function getCartItems(User $user): Collection
    {
        return CartItem::with('product')->where('user_id', $user->id)->get();
    }

    /**
     * Update the quantity of a cart item. Supports fallback to product_id.
     */
    public function updateQuantity(User $user, ?int $cartItemId, ?int $productId, int $quantity): void
    {
        if ($cartItemId) {
            CartItem::where('user_id', $user->id)
                ->where('id', $cartItemId)
                ->update(['quantity' => $quantity]);
        } elseif ($productId) {
            CartItem::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->update(['quantity' => $quantity]);
        }
    }

    /**
     * Remove an item from the cart. Supports fallback to product_id.
     */
    public function removeItem(User $user, ?int $cartItemId, ?int $productId): void
    {
        if ($cartItemId) {
            CartItem::where('user_id', $user->id)
                ->where('id', $cartItemId)
                ->delete();
        } elseif ($productId) {
            CartItem::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->delete();
        }
    }

    /**
     * Get total quantity of all items in the user's cart.
     */
    public function getCartCount(User $user): int
    {
        return (int) CartItem::where('user_id', $user->id)->sum('quantity');
    }

    /**
     * Clear all items in the user's cart.
     */
    public function clearCart(User $user): void
    {
        CartItem::where('user_id', $user->id)->delete();
    }
}
