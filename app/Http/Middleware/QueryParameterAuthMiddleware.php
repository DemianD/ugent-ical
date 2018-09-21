<?php

namespace App\Http\Middleware;

use Closure;

class QueryParameterAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if(!config('custom.authentication.enabled')) {
            return $next($request);
        }

        if(request('key') === config('custom.authentication.password')) {
            return $next($request);
        }   

        return response()->json(['error' => 'Unauthenticated.'], 401);
    }
}
