<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Cegah kebocoran detail teknis error (query SQL, password, path server) ke layar pengguna
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Biarkan penanganan error HTTP standar (404, 403, 419, validasi 422, otentikasi) berjalan normal
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
                || $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            // Catat detail teknis secara lengkap ke log sistem untuk keperluan pemeliharaan
            \Illuminate\Support\Facades\Log::error('System Exception: ' . $e->getMessage(), [
                'url'       => $request->fullUrl(),
                'method'    => $request->method(),
                'user_id'   => auth()->id(),
                'exception' => get_class($e),
                'file'      => $e->getFile() . ':' . $e->getLine(),
            ]);

            // Untuk request AJAX / API: kirim response JSON yang bersih dan ramah pengguna
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan pada sistem. Silakan coba beberapa saat lagi.',
                ], 500);
            }

            // Untuk aksi form (POST/PUT/PATCH/DELETE): redirect kembali dengan pesan aman dan input dipertahankan
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                return back()->withInput()->with('error', 'Terjadi kendala pada sistem saat memproses data. Silakan coba kembali atau hubungi pemilik.');
            }

            // Untuk permintaan halaman web (GET): tampilkan halaman error 500 kustom yang aman
            return response()->view('errors.500', [], 500);
        });
    })->create();
