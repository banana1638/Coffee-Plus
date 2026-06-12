<?php

namespace App\Services;

use App\Contracts\FavoriteServiceInterface;
use App\Models\Favorite;
use App\Models\User;
use App\Support\AddonsSignature;
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
        $addonsArray = AddonsSignature::normalize($addons);
        $addonsSignature = AddonsSignature::from($addonsArray);

        /** @var Favorite|null $favorite */
        $favorite = Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->where('addons_signature', $addonsSignature)
            ->first();

        if (!$favorite) {
            $favorite = $this->findLegacyFavorite($user, $productId, $size, $temp, $addonsArray);
        }

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
        $favorite->addons_signature = $addonsSignature;
        $favorite->remark = $remark ?? '';
        $favorite->save();

        return 'added';
    }

    /**
     * Check if a favorite item exists.
     */
    public function check(User $user, int $productId, string $size, string $temp, array $addons): bool
    {
        $addonsArray = AddonsSignature::normalize($addons);
        $addonsSignature = AddonsSignature::from($addonsArray);

        if (Favorite::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->where('size', $size)
            ->where('temp', $temp)
            ->where('addons_signature', $addonsSignature)
            ->exists()) {
            return true;
        }

        return $this->findLegacyFavorite($user, $productId, $size, $temp, $addonsArray) !== null;
    }

    /**
     * Add a favorite item. Throws exception if it already exists (RESTful style).
     */
    public function add(User $user, int $productId, string $size, string $temp, array $addons, ?string $remark): Favorite
    {
        $addonsArray = AddonsSignature::normalize($addons);
        $addonsSignature = AddonsSignature::from($addonsArray);

        if ($this->check($user, $productId, $size, $temp, $addonsArray)) {
            throw new Exception('Favorite already exists', 409);
        }

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $productId;
        $favorite->size = $size;
        $favorite->temp = $temp;
        $favorite->addons = $addonsArray;
        $favorite->addons_signature = $addonsSignature;
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

    private function findLegacyFavorite(User $user, int $productId, string $size, string $temp, array $addonsArray): ?Favorite
    {
        return Favorite::where('user_id', $user->id)
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
