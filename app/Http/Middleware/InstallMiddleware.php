<?php

namespace App\Http\Middleware;

use App\Models\Config;
use Closure;
use Illuminate\Http\Request;

class InstallMiddleware
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
        $installed = config('app.installed');

        if (!$installed && !preg_match('/install/', $request->url())) {
            return redirect('/install');
        }

        if ($installed && preg_match('/install/', $request->url())) {
            abort(404);
        }

        return $next($request);
    }
}
