<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header keamanan pada setiap respons (anti clickjacking,
 * MIME sniffing, kebocoran referrer, dan Content-Security-Policy).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Lewati untuk unduhan file/streamed response yang bukan HTML.
        $headers = $response->headers;

        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-XSS-Protection', '0');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // CSP: mengizinkan CDN yang dipakai (Tailwind, Alpine, TinyMCE, Google Fonts),
        // gambar data:/https:, serta membatasi frame-ancestors, object, dan base-uri.
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://cdn.jsdelivr.net",
            "media-src 'self' https: data:",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
        $headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
