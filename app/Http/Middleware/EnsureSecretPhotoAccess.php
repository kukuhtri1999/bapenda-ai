<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecretPhotoAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if password is already verified in session
        if (session('photo_access_verified')) {
            return $next($request);
        }

        // Check for password in request
        $password = $request->input('password');

        if ($password === 'L4M0n64N6M361L4N') {
            session(['photo_access_verified' => true]);
            return $next($request);
        }

        // Return JSON for AJAX requests, redirect for regular requests
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Password required',
                'requires_password' => true
            ], 401);
        }

        // For regular requests, show password form or redirect
        return redirect()->route('photo.login');
    }
}
