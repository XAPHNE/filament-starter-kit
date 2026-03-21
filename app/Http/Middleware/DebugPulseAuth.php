<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class DebugPulseAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('DebugPulseAuth:', [
            'url' => $request->fullUrl(),
            'user_id' => Auth::id(),
            'check' => Auth::check(),
            'guard' => Auth::getDefaultDriver(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : 'no session',
            'cookies' => $request->cookies->all(),
        ]);

        return $next($request);
    }
}
