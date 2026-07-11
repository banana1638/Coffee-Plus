<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSharedRecipeRequest;
use App\Models\SharedRecipe;
use App\Contracts\CartServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SharedRecipeController extends Controller
{
    use ApiResponse;

    protected CartServiceInterface $cartService;

    public function __construct(CartServiceInterface $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * List recipes shared with the authenticated user.
     */
    public function index(Request $request)
    {
        $recipes = SharedRecipe::where('recipient_id', $request->user()->id)
            ->with(['sender:id,name', 'product'])
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'status' => 'success',
            'data' => $recipes->items(),
            'meta' => [
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'per_page' => $recipes->perPage(),
                'total' => $recipes->total(),
            ],
        ]);
    }

    /**
     * Share a recipe with a friend.
     */
    public function store(StoreSharedRecipeRequest $request)
    {
        $validated = $request->validated();

        // Ensure the recipient is an accepted friend
        $isFriend = $request->user()->friends()
            ->where('friend_id', $validated['recipient_id'])
            ->exists();

        if (!$isFriend) {
            return $this->error('You can only share recipes with accepted friends.', 403);
        }

        $recipe = new SharedRecipe();
        $recipe->sender_id = $request->user()->id;
        $recipe->recipient_id = $validated['recipient_id'];
        $recipe->product_id = $validated['product_id'];
        $recipe->name = $validated['name'];
        $recipe->size = $validated['size'];
        $recipe->temp = $validated['temp'];
        $recipe->addons = $validated['addons'] ?? [];
        $recipe->remark = $validated['remark'] ?? null;
        $recipe->save();

        return $this->success($recipe->load(['sender:id,name', 'product']), 'Recipe shared!', 201);
    }

    /**
     * Import a shared recipe into user's cart.
     */
    public function import(Request $request, $id)
    {
        $recipe = SharedRecipe::where('recipient_id', $request->user()->id)
            ->findOrFail($id);

        $this->cartService->add(
            $request->user(),
            $recipe->product_id,
            1,
            $recipe->size,
            $recipe->temp,
            $recipe->addons ?? []
        );

        return $this->success(null, 'Recipe added to your cart!');
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 20), 1), 50);
    }
}
