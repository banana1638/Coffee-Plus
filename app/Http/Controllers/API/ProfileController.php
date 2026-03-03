<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        return response()->json(['user' => new UserResource($request->user())]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($request->user())
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->currentAccessToken()->delete();

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully.'
        ]);
    }
}
