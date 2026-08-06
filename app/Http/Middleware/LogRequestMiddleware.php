<?php

namespace App\Http\Middleware;

use App\Models\LogApi;
use Closure;
use Illuminate\Http\Request;

class LogRequestMiddleware
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
        if (env('API_REQUEST_DB_LOG_ENABLED', false)) {
            try {
                LogApi::create(
                    [
                        'ip' => $request->getClientIp(),
                        'url' => $request->path(),
                        'header' => json_encode($request->headers->all()),
                        'body' => $request->getContent()
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('Error al registrar API request log: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
}
