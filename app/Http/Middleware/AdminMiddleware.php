<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Attempt to authenticate the user using JWT
            $user = JWTAuth::parseToken()->authenticate();
            
            // Here you can add role checking logic
            // For now, we'll assume any authenticated user can access admin functions
            // In a real application, you'd check if the user has admin role/permission
            
            // Example role check (uncomment and modify as needed):
            // if (!$user->hasRole('admin')) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Access denied. Admin privileges required.'
            //     ], 403);
            // }

        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired'
            ], 401);

        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid'
            ], 401);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token absent'
            ], 401);
        }

        return $next($request);
    }
}