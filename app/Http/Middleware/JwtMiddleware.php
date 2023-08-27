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
                return ResponseFormatter::error(message: 'Token is Invalid', code: 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                try {
                    $newToken = auth()->refresh(true, true);
                    return ResponseFormatter::error(message: 'Token is Expired', data:$newToken, code: 401);
                } catch (\Throwable) {
                    return ResponseFormatter::error(message: 'Session have been expired', code: 401);
                }
            } else {
                return ResponseFormatter::error(message: 'Authorization Token not found', code: 200);
            }
        }
        return $next($request);
    }
}
