<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidatePurchaseCode
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
        if (config('qrcode.market_place') === 'gumroad') {
            return $next($request);
        }

        if (preg_match('/install/', $request->url())) return $next($request);

        if (!config('app.purchase_code')) {
            $response = new Response('You need to get a purchase code to run the script', 403);
            return $response;
        }

        return $next($request);
    }
}
