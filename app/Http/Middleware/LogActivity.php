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
                && $response->getStatusCode() < 400
                && ! $request->attributes->get('observe_blocked')) {
                $route = optional($request->route())->getName();
                // Jangan catat login/logout di sini (dicatat khusus di controller).
                if (! in_array($route, ['login', 'logout'], true)) {
                    // Selama Mode Observasi, pelaku sebenarnya adalah superadmin (impersonator).
                    $impersonatorId = $request->session()->get('impersonator_id');
                    $description = $this->describe($route, $request);
                    if ($impersonatorId) {
                        $description = mb_substr('[Observasi sebagai ' . Auth::user()->name . '] ' . $description, 0, 255);
                    }
                    ActivityLog::create([
                        'user_id' => $impersonatorId ?: Auth::id(),
                        'action' => strtolower($request->method()),
                        'description' => $description,
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
