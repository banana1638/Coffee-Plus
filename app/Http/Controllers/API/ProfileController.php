<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\ApiTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileController extends Controller
{
    public function __construct(private readonly ApiTokenService $apiTokenService) {}

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
            'user' => new UserResource($request->user()),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $deviceName = $currentToken instanceof PersonalAccessToken ? $currentToken->name : null;
        $user->password = Hash::make($validated['password']);
        $user->save();

        // Revoke all existing tokens to block unauthorized persistent access
        $user->tokens()->delete();

        // Issue a fresh token for the current session
        $newToken = $this->apiTokenService->issue($user, $deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated. Please use the new token.',
            'access_token' => $newToken,
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

        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Get user's notifications.
     */
    public function notifications(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = $request->user()->unreadNotifications()->where('id', $id)->first();
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
        ]);
    }

    public function batchDeleteNotifications(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! empty($ids)) {
            $request->user()->notifications()->whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Notifications deleted successfully']);
        }

        return response()->json(['message' => 'No notifications selected'], 400);
    }

    public function deleteReadNotifications(Request $request)
    {
        $request->user()->readNotifications()->delete();

        return response()->json(['message' => 'Read notifications deleted successfully']);
    }

    private function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 20), 1), 50);
    }
}
