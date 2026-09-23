<?php

use App\Services\ValidationMessageService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'          => \App\Http\Middleware\RoleMiddleware::class,
            'branch.access' => \App\Http\Middleware\BranchAccessMiddleware::class,
            'optional.auth' => \App\Http\Middleware\OptionalSanctumAuth::class,
        ]);

        // Render terminates TLS at its edge and forwards plain HTTP with
        // X-Forwarded-* headers — trust it so scheme/secure detection works.
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();

        $middleware->redirectGuestsTo(function ($request) {
            if ($request->expectsJson()) {
                return null;
            }
            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
            ], 404);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The requested resource was not found.',
            ], 404);
        });

        $exceptions->renderable(function (ValidationException $e) {
            $errors = $e->errors();
            $friendlyErrors = ValidationMessageService::translateErrors($errors);
            
            // Get first error for quick message
            $firstError = $friendlyErrors[0] ?? 'Validation failed. Please check your input.';
            
            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors,
                'friendly_errors' => $friendlyErrors,
            ], 422);
        });

        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to perform this action.',
            ], 403);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'You don\'t have permission to perform this action.',
            ], 403);
        });

        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Your session has expired. Please sign in again.',
            ], 401);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please wait a minute before trying again.',
            ], 429);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This action is not allowed.',
            ], 405);
        });

        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Invalid request.',
            ], 400);
        });

        // Framework HTTP errors carry their own status (419 expired CSRF token,
        // 413 upload too large, 503 maintenance…). Without this, the catch-all
        // below reported all of them as 500, so clients couldn't react.
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $status = $e->getStatusCode();
            $messages = [
                413 => 'The uploaded file is too large.',
                419 => 'Your session has expired. Please refresh the page and try again.',
                503 => 'The service is temporarily unavailable. Please try again shortly.',
            ];

            return response()->json([
                'success' => false,
                'message' => $messages[$status]
                    ?? ($status < 500 ? 'The request could not be completed.' : 'Something went wrong on our end. Please try again later.'),
            ], $status, $e->getHeaders());
        });

        // Catch-all for unhandled exceptions
        $exceptions->renderable(function (\Throwable $e) {
            // Log the actual error for debugging
            \Illuminate\Support\Facades\Log::error('Unhandled exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong on our end. Please try again later.',
            ], 500);
        });

        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return true;
        });
    })->create();
