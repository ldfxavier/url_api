<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class apiProtectedRoute extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $user = auth('api')->user();

        if($user){
            try {
                $user = JWTAuth::parseToken()->authenticate();
            } catch (\Exception $e) {
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) :
                    return response([
                        'error' => true,
                        'message' => 'Token inválido!',
                    ], 401);
                elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) :
                    return response([
                        'error' => true,
                        'message' => 'Token expirado!',
                    ], 401);
                else :
                    return response([
                        'error' => true,
                        'message' => 'Token não encontrado!',
                    ], 401);
                endif;
            }
            return $next($request);
        }
        return response([
            'error' => true,
            'message' => 'Você não está logado ou não tem autorização para acessar essa rota!',
        ], 401);
    }
}
