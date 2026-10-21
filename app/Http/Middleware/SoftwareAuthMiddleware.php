<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

class SoftwareAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin_software')->check()) {
            Auth::shouldUse('admin_software');
            return $next($request);
        }
        if (Auth::guard('team_person')->check()) {
            Auth::shouldUse('team_person');
            return $next($request);
        }
        if (Auth::guard('customer')->check()) {
            Auth::shouldUse('customer');
            return $next($request);
        }
        // dd("SoftwareAuthMiddleware 24", Auth::user());

        // If it's an AJAX request, return JSON response
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Session expired. Please login again.',
                'redirect' => route('software.login'),
                'session_expired' => true
            ], 401);
        }

        return Redirect::route('software.login');
    }
}
