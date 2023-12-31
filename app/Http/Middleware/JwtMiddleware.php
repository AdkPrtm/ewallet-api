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
                return ResponseFormatter::error('', 'Token is Invalid', 401);
            } elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                try {
                    $newToken = auth()->refresh(true, true);
                    return ResponseFormatter::success(data: [
                        'token' => $newToken,
                    ], message: 'Token is Expired', code: 200);
                } catch (\Throwable) {
                    return ResponseFormatter::error('', 'Session have been expired, trying to relogin.', 401);
                }
            } else {
                return ResponseFormatter::error('', 'Authorization Token not found', 401);
            }
        }
        return $next($request);
    }
}
