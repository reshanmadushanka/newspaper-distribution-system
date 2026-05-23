<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated and has a locale preference, use it
        if ($request->user()?->locale) {
            session(['locale' => $request->user()->locale]);
            app()->setLocale($request->user()->locale);
        } else {
            // Otherwise use session locale or fallback to config default
            $locale = session('locale', config('app.locale', 'en'));
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
