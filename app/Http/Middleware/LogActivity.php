<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mencatat aktivitas tulis (POST/PUT/PATCH/DELETE) pengguna terautentikasi
 * ke tabel activity_logs untuk audit / pemantauan per user.
 */
class LogActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            if (Auth::check()
                && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
                && $response->getStatusCode() < 400) {
                $route = optional($request->route())->getName();
                // Jangan catat login/logout di sini (dicatat khusus di controller).
                if (! in_array($route, ['login', 'logout'], true)) {
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => strtolower($request->method()),
                        'description' => $this->describe($route, $request),
                        'method' => $request->method(),
                        'route' => $route,
                        'url' => mb_substr($request->fullUrl(), 0, 1024),
                        'ip' => $request->ip(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Jangan pernah menggagalkan request karena logging.
        }

        return $response;
    }

    private function describe(?string $route, Request $request): string
    {
        if (! $route) {
            return strtoupper($request->method()) . ' ' . $request->path();
        }

        return str_replace(['admin.', '.'], ['', ' › '], $route);
    }
}
