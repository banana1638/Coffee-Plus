<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SharedRecipe;
use App\Contracts\CartServiceInterface;
use App\Support\ProductAddonSelection;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
    public function store(Request $request)
    {
        $sizes = collect(config('coffee.options.sizes', []))->pluck('name')->all();
        $temps = config('coffee.options.temps', []);

        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:100',
            'size' => ['required', 'string', Rule::in($sizes)],
            'temp' => ['required', 'string', Rule::in($temps)],
            'addons' => 'nullable|array|max:20',
            'addons.*' => 'string|max:100',
            'remark' => 'nullable|string|max:1000',
        ]);

        $validator->after(function ($validator) use ($request) {
            $productId = (int) $request->input('product_id');
            $addons = ProductAddonSelection::normalize($request->input('addons', []));

            if (!ProductAddonSelection::belongsToProduct($productId, $addons)) {
                $validator->errors()->add('addons', 'Selected add-ons are invalid for this product.');
            }
        });

        $validator->validate();

        // Ensure the recipient is an accepted friend
        $isFriend = $request->user()->friends()
            ->where('friend_id', $request->recipient_id)
            ->exists();

        if (!$isFriend) {
            return $this->error('You can only share recipes with accepted friends.', 403);
        }

        $recipe = new SharedRecipe();
        $recipe->sender_id = $request->user()->id;
        $recipe->recipient_id = $request->recipient_id;
        $recipe->product_id = $request->product_id;
        $recipe->name = $request->name;
        $recipe->size = $request->size;
        $recipe->temp = $request->temp;
        $recipe->addons = $request->input('addons', []);
        $recipe->remark = $request->remark;
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
