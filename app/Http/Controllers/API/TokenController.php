<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $currentTokenId = $request->user()->currentAccessToken()?->getKey();
        $tokens = $request->user()->tokens()->orderByDesc('id')->get()->map(fn ($token) => [
            'id' => $token->id,
            'device_name' => $token->name,
            'is_current' => $token->id === $currentTokenId,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
        ]);

        return $this->success(['tokens' => $tokens]);
    }

    public function destroy(Request $request, int $token)
    {
        $currentTokenId = $request->user()->currentAccessToken()?->getKey();
        $deleted = $request->user()->tokens()->whereKey($token)->delete();

        if ($deleted !== 1) {
            return $this->error('Token not found.', 404);
        }

        return $this->success([
            'revoked_current' => $token === $currentTokenId,
        ], 'Device token revoked.');
    }

    public function destroyAll(Request $request)
    {
        $revokedCount = $request->user()->tokens()->delete();

        return $this->success([
            'revoked_count' => $revokedCount,
        ], 'All device tokens revoked.');
    }
}
