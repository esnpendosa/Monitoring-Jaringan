<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainLock
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Izinkan akses ke endpoint Fingerprint tanpa pengecekan domain
        if ($request->is('iclock/*') || $request->is('fingerspot/*')) {
            return $next($request);
        }

        $licensedDomain = env('LICENSED_DOMAIN');

        // Jika LICENSED_DOMAIN tidak diatur, izinkan akses (Optional)
        if (!$licensedDomain) {
            return $next($request);
        }

        $currentHost = $request->getHost();

        // Cek apakah host saat ini sesuai dengan domain berlisensi
        if ($currentHost !== $licensedDomain && $currentHost !== 'localhost' && $currentHost !== '127.0.0.1') {
            return response()->view('errors.license', [
                'current' => $currentHost,
                'licensed' => $licensedDomain
            ], 403);
        }

        return $next($request);
    }
}
