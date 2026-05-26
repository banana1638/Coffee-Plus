<?php

namespace App\Contracts;

use App\Models\User;
use App\Models\Favorite;
use Illuminate\Support\Collection;

interface FavoriteServiceInterface
{
    /**
     * Get all favorites with product relation for the user.
     */
    public function getFavorites(User $user): Collection;

    /**
     * Toggle a favorite item. Returns 'added' or 'removed'.
     */
    public function toggle(User $user, int $productId, string $size, string $temp, array $addons, ?string $remark): string;

    /**
     * Check if a favorite item exists.
     */
    public function check(User $user, int $productId, string $size, string $temp, array $addons): bool;

    /**
     * Add a favorite item. Throws exception if it already exists (RESTful style).
     */
    public function add(User $user, int $productId, string $size, string $temp, array $addons, ?string $remark): Favorite;

    /**
     * Delete a favorite item by ID.
     */
    public function delete(User $user, int $id): void;
}
