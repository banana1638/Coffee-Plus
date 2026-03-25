<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'size' => 'required|string',
            'temp' => 'required|string',
            'addons' => 'nullable|array',
            'remark' => 'nullable|string',
        ]);

        $user = Auth::user();

        $addonsArray = $request->addons ?? [];
        sort($addonsArray);

        /** @var \App\Models\Favorite|null $favorite */
        $favorite = Favorite::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->where('temp', $request->temp)
            ->get()
            ->first(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });

        if ($favorite) {
            $favorite->delete();
            return response()->json(['status' => 'removed']);
        }

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->product_id = $request->product_id;
        $favorite->size = $request->size;
        $favorite->temp = $request->temp;
        $favorite->addons = $addonsArray;
        $favorite->remark = $request->remark ?? '';
        $favorite->save();

        return response()->json(['status' => 'added']);
    }

    public function check(Request $request)
    {
        $user = Auth::user();
        if (!$user)
            return response()->json(['is_favorite' => false]);

        $addonsArray = $request->addons ?? [];
        sort($addonsArray);

        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('size', $request->size)
            ->where('temp', $request->temp)
            ->get()
            ->contains(function ($item) use ($addonsArray) {
                $itemAddons = is_array($item->addons) ? $item->addons : [];
                sort($itemAddons);
                return $itemAddons === $addonsArray;
            });

        return response()->json(['is_favorite' => $isFavorite]);
    }

    public function destroy($id)
    {
        $favorite = Favorite::where('user_id', Auth::id())->findOrFail($id);
        $favorite->delete();
        return response()->json(['status' => 'success']);
    }
}
