<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
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
        if (Auth::guard($guard)->check()) {
            return redirect('/home');
        }

        if(\App\School::find(1) == null){
            //redirect to addSchool
        }

        // if(\App::clazz()->get()->count() == 0){
        //     //redirect to add atleast a clazz
        // }

        return $next($request);
    }
}
