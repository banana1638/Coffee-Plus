<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = $request->user('admin');

        if (!$admin) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            if (!$admin->canPerform($permission)) {
                abort(403);
            }
        }

        return $next($request);
    }
}
