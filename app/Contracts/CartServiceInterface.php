<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\CartItem;
use Illuminate\Support\Collection;

interface CartServiceInterface
{
    /**
     * Add or update an item in the user's cart.
     */
    public function add(User $user, int $productId, int $quantity, string $size, string $temp, array $addons): CartItem;

    /**
     * Get all cart items with product relation for the user.
     */
    public function getCartItems(User $user): Collection;

    /**
     * Update the quantity of a cart item. Supports fallback to product_id.
     */
    public function updateQuantity(User $user, ?int $cartItemId, ?int $productId, int $quantity): void;

    /**
     * Remove an item from the cart. Supports fallback to product_id.
     */
    public function removeItem(User $user, ?int $cartItemId, ?int $productId): void;

    /**
     * Get total quantity of all items in the user's cart.
     */
    public function getCartCount(User $user): int;

    /**
     * Clear all items in the user's cart.
     */
    public function clearCart(User $user): void;
}
