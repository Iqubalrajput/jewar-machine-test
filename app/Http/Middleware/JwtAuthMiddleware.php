<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Attempt to authenticate using the token
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            // Return error response if token is invalid or missing
            return response()->json(['error' => 'Unauthorized. Invalid or missing token.'], 401);
        }

        return $next($request);
    }
    // public function handle(Request $request, Closure $next)
    // {
    //     return $next($request);
    // }
}
