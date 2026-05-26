<?php

namespace App\Services;

use App\Contracts\FavoriteServiceInterface;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Support\Collection;
use Exception;

class FavoriteService implements FavoriteServiceInterface
{
    /**
     * Get all favorites with product relation for the user.
     */
    public function getFavorites(User $user): Collection
    {
        return Favorite::with('product')->where('user_id', $user->id)->latest()->get();
    }

    /**
     * Toggle a favorite item. Returns 'added' or 'removed'.
     */
    public function toggle(User $user, int $productId, string $size, string $temp, array $addons, ?string $remark): string
    {
        $addonsArray = $addons;
        sort($addonsArray);

        /** @var Favorite|null $favorite */
        $favorite = Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->get()
            ->first(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });

        if ($favorite) {
            $favorite->delete();
            return 'removed';
        }

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $productId;
        $favorite->size = $size;
        $favorite->temp = $temp;
        $favorite->addons = $addonsArray;
        $favorite->remark = $remark ?? '';
        $favorite->save();

        return 'added';
    }

    /**
     * Check if a favorite item exists.
     */
    public function check(User $user, int $productId, string $size, string $temp, array $addons): bool
    {
        $addonsArray = $addons;
        sort($addonsArray);

        return Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->get()
            ->contains(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });
    }

    /**
     * Add a favorite item. Throws exception if it already exists (RESTful style).
     */
    public function add(User $user, int $productId, string $size, string $temp, array $addons, ?string $remark): Favorite
    {
        $addonsArray = $addons;
        sort($addonsArray);

        if ($this->check($user, $productId, $size, $temp, $addonsArray)) {
            throw new Exception('Favorite already exists', 409);
        }

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $productId;
        $favorite->size = $size;
        $favorite->temp = $temp;
        $favorite->addons = $addonsArray;
        $favorite->remark = $remark ?? '';
        $favorite->save();

        return $favorite;
    }

    /**
     * Delete a favorite item by ID.
     */
    public function delete(User $user, int $id): void
    {
        $favorite = Favorite::where('user_id', $user->id)->findOrFail($id);
        $favorite->delete();
    }
}
