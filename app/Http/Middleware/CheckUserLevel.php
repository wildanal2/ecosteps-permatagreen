<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserLevel
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $userLevel = auth()->user()->user_level;

            // Admin (level 2) hanya bisa akses route admin.*
            if ($userLevel == 2 && !$request->routeIs('admin.*') && !$request->routeIs('profile.*') && !$request->routeIs('user-password.*') && !$request->routeIs('appearance.*') && !$request->routeIs('two-factor.*')) {
                return redirect()->route('admin.dashboard');
            }

            // Karyawan (level 1) hanya bisa akses route dashboard & riwayat
            if ($userLevel == 1 && !$request->routeIs('dashboard') && !$request->routeIs('riwayat') && !$request->routeIs('employee.*') && !$request->routeIs('profile.*') && !$request->routeIs('user-password.*') && !$request->routeIs('appearance.*') && !$request->routeIs('two-factor.*')) {
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
