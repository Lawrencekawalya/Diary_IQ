<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireForcedPasswordChange
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user()?->force_password_change === true
            && ! $request->routeIs('password.force.edit', 'password.force.update', 'logout')
        ) {
            return redirect()->route('password.force.edit');
        }

        return $next($request);
    }
}
