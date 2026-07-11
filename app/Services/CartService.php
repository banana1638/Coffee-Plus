<?php

namespace App\Services;

use App\Contracts\CartServiceInterface;
use App\Contracts\PricingServiceInterface;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Support\AddonsSignature;
use App\Support\Money;
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
        $this->assertValidQuantity($quantity);

        $product = Product::findOrFail($productId);
        $finalUnitPriceCents = $this->pricingService->calculateUnitPriceCents($product, $size, $addons);
        $finalUnitPrice = Money::fromCents($finalUnitPriceCents);

        $addonsArray = AddonsSignature::normalize($addons);
        $addonsSignature = AddonsSignature::from($addonsArray);

        /** @var CartItem|null $cartItem */
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->where('addons_signature', $addonsSignature)
            ->first();

        if (!$cartItem) {
            $cartItem = $this->findLegacyCartItem($user, $productId, $size, $temp, $addonsArray);
        }

        if ($cartItem) {
            $this->assertValidQuantity($cartItem->quantity + $quantity);

            $cartItem->quantity += $quantity;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->unit_price_cents = $finalUnitPriceCents;
            $cartItem->addons_signature = $addonsSignature;
            $cartItem->save();
        } else {
            $cartItem = new CartItem();
            $cartItem->user_id = $user->id;
            $cartItem->product_id = $productId;
            $cartItem->quantity = $quantity;
            $cartItem->size = $size;
            $cartItem->temp = $temp;
            $cartItem->addons = $addonsArray;
            $cartItem->addons_signature = $addonsSignature;
            $cartItem->unit_price = $finalUnitPrice;
            $cartItem->unit_price_cents = $finalUnitPriceCents;
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
        $this->assertValidQuantity($quantity);

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

    private function assertValidQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 20) {
            throw new \InvalidArgumentException('Cart quantity must be between 1 and 20.');
        }
    }

    private function findLegacyCartItem(User $user, int $productId, string $size, string $temp, array $addonsArray): ?CartItem
    {
        return CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->whereNull('addons_signature')
            ->get()
            ->first(function ($item) use ($addonsArray) {
                return AddonsSignature::normalize(is_array($item->addons) ? $item->addons : []) === $addonsArray;
            });
    }
}
