<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware untuk memverifikasi role pengguna.
 *
 * Cara mendaftarkan di bootstrap/app.php (Laravel 11+):
 *
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'role' => \App\Http\Middleware\CheckRole::class,
 *       ]);
 *   })
 *
 * Cara pakai di route:
 *   Route::middleware(['auth:api', 'role:penyedia'])
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated. Token tidak ditemukan.',
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'status'  => false,
                'message' => "Akses ditolak. Hanya {$role} yang dapat melakukan aksi ini.",
            ], 403);
        }

        return $next($request);
    }
}