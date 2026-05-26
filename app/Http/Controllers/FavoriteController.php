<?php

namespace App\Http\Controllers;

use App\Contracts\FavoriteServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    protected FavoriteServiceInterface $favoriteService;

    public function __construct(FavoriteServiceInterface $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
            'temp' => 'required|string',
            'addons' => 'nullable|array',
            'remark' => 'nullable|string',
        ]);

        $status = $this->favoriteService->toggle(
            Auth::user(),
            (int) $request->product_id,
            $request->size,
            $request->temp,
            $request->input('addons', []) ?? [],
            $request->remark
        );

        return response()->json(['status' => $status]);
    }

    public function check(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['is_favorite' => false]);
        }

        $isFavorite = $this->favoriteService->check(
            $user,
            (int) $request->product_id,
            $request->size,
            $request->temp,
            $request->input('addons', []) ?? []
        );

        return response()->json(['is_favorite' => $isFavorite]);
    }

    public function destroy($id)
    {
        $this->favoriteService->delete(Auth::user(), (int) $id);
        return response()->json(['status' => 'success']);
    }
}
