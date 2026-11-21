<?php

use App\Http\Middleware\BroadcastApiChange;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // This tells Laravel to trust the Tailscale Funnel headers
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', ''));
        $middleware->statefulApi();
        $middleware->api(append: BroadcastApiChange::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('broadcasting/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*') && ! $request->is('broadcasting/*')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof HttpResponseException) {
                return null;
            }

            $status = match (true) {
                $e instanceof AuthenticationException => 401,
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => 403,
                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => 404,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            $customMessage = $e instanceof HttpException
                && ! $e instanceof AccessDeniedHttpException
                && ! $e instanceof NotFoundHttpException
                && in_array($status, [401, 403, 404], true)
                && $e->getMessage() !== ''
                && ! in_array($e->getMessage(), [
                    'Forbidden',
                    'Not Found',
                    'Unauthorized',
                    'This action is unauthorized.',
                    'Unauthenticated.',
                ], true)
                ? $e->getMessage()
                : null;

            $message = match (true) {
                $status === 401 => $customMessage ?? 'Silakan masuk terlebih dahulu.',
                $status === 403 => $customMessage ?? 'Anda tidak memiliki izin untuk tindakan ini.',
                $status === 404 => $customMessage ?? 'Data yang diminta tidak ditemukan.',
                $status >= 500 => config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada server.',
                default => null,
            };

            return $message === null ? null : response()->json(['message' => $message], $status);
        });
    })->create();
