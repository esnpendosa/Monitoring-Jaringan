<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictIntern
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user && $user->id_role == 5) {
            // Allowed routes for Intern
            $allowedRoutes = ['dashboard', 'intern.tasks.update-status', 'logout'];
            $currentRoute = $request->route() ? $request->route()->getName() : null;

            if (!in_array($currentRoute, $allowedRoutes)) {
                abort(403, 'Akses ditolak. Sebagai magang, Anda hanya diperbolehkan mengakses dashboard dan task list.');
            }
        }

        return $next($request);
    }
}
