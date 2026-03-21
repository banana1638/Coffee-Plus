<?php

namespace App\Http\Controllers\Api;

use App\Models\Favorite;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FavoriteResource;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()->with('product')->latest()->get();
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

        $favorite = new Favorite();
        $favorite->user_id = $request->user()->id;
        $favorite->product_id = $request->product_id;
        $favorite->size = $request->size;
        $favorite->temp = $request->temp;
        $favorite->addons = $request->addons ?? [];
        $favorite->remark = $request->remark;
        $favorite->save();

        return new FavoriteResource($favorite->load('product'));
    }

    public function destroy($id)
    {
        $favorite = auth()->user()->favorites()->findOrFail($id);
        $favorite->delete();

        return response()->json(['message' => 'Favorite removed']);
    }
}
