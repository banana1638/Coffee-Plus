<?php

namespace App\Http\Controllers\API;

use App\Contracts\FavoriteServiceInterface;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FavoriteResource;

class FavoriteController extends Controller
{
    protected FavoriteServiceInterface $favoriteService;

    public function __construct(FavoriteServiceInterface $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    public function index(Request $request)
    {
        $favorites = $this->favoriteService->getFavorites($request->user());
        return FavoriteResource::collection($favorites);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
            'temp' => 'required|string',
            'addons' => 'array',
            'remark' => 'nullable|string',
        ]);

        try {
            $favorite = $this->favoriteService->add(
                $request->user(),
                (int) $request->product_id,
                $request->size,
                $request->temp,
                $request->input('addons', []) ?? [],
                $request->remark
            );

            return (new FavoriteResource($favorite->load('product')))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 400);
        }
    }

    public function destroy($id)
    {
        $this->favoriteService->delete(auth()->user(), (int) $id);

        return response()->json(['message' => 'Favorite removed']);
    }
}
