<?php

namespace App\Http\Middleware;

use Closure;

class Admin
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
        $user = auth()->user();
        if (! $user->hasRole('admin') && ! $user->hasRole('personal')) {
            return redirect('/dataset/create');
        }

        return $next($request);
    }
}
