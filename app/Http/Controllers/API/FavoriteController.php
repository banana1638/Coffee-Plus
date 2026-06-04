<?php

namespace App\Http\Controllers\API;

use App\Contracts\FavoriteServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FavoriteResource;
use App\Http\Requests\API\StoreFavoriteRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    use ApiResponse;

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

    public function store(StoreFavoriteRequest $request)
    {
        try {
            $favorite = $this->favoriteService->add(
                $request->user(),
                (int) $request->product_id,
                $request->size,
                $request->temp,
                $request->input('addons', []) ?? [],
                $request->remark
            );

            return $this->success(
                new FavoriteResource($favorite->load('product')),
                'Favorite added successfully!',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode() ?: 400);
        }
    }

    public function destroy($id)
    {
        $this->favoriteService->delete(auth()->user(), (int) $id);

        return $this->success(null, 'Favorite removed');
    }
}
