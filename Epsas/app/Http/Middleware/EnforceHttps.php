<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('production.enforce_https') || $request->isSecure()) {
            return $next($request);
        }

        $secureUrl = preg_replace('/^http:/i', 'https:', $request->fullUrl());

        return redirect()->to($secureUrl, Response::HTTP_TEMPORARY_REDIRECT);
    }
}
