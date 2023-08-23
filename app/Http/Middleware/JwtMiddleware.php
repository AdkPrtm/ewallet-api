<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Helpers\ResponseFormatter;
use Exception;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
            return ResponseFormatter::error([], 'Token is Invalid', 200);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
            return ResponseFormatter::error([], 'Token is Expired', 200);
            } else {
            return ResponseFormatter::error([], 'Authorization Token not found', 200);
            }
        }
        return $next($request);
    }
}
