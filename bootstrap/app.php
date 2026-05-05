<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'perm' => \App\Http\Middleware\EnsurePermission::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (
                ($e instanceof ValidationException || $e instanceof AuthenticationException)
                && ! ($request->expectsJson() || $request->is('api/*'))
            ) {
                return null;
            }

            $status = match (true) {
                $e instanceof ValidationException => $e->status,
                $e instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
                $e instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
                $e instanceof TokenMismatchException => 419,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            $message = match ($status) {
                Response::HTTP_UNAUTHORIZED => 'Please sign in to continue.',
                Response::HTTP_FORBIDDEN => 'You do not have permission to perform this action.',
                Response::HTTP_NOT_FOUND => 'We could not find what you were looking for.',
                Response::HTTP_METHOD_NOT_ALLOWED => 'This action is not available from here.',
                419 => 'Your session expired. Please refresh and try again.',
                Response::HTTP_TOO_MANY_REQUESTS => 'Too many attempts. Please wait a moment and try again.',
                Response::HTTP_SERVICE_UNAVAILABLE => 'The service is temporarily unavailable. Please try again shortly.',
                default => $status >= 500
                    ? 'Something went wrong while processing your request. Please try again.'
                    : ($e->getMessage() ?: 'Unable to complete this request.'),
            };

            if ($request->expectsJson() || $request->is('api/*')) {
                $payload = [
                    'ok' => false,
                    'message' => $message,
                ];

                if ($status === Response::HTTP_UNPROCESSABLE_ENTITY && $e instanceof ValidationException) {
                    $payload['errors'] = $e->errors();
                    $payload['message'] = Arr::first(Arr::flatten($e->errors())) ?: $message;
                }

                $response = response()->json($payload, $status);
                $response->headers->set('Cache-Control', 'no-store, private');

                return $response;
            }

            return response()->view('errors.generic', [
                'status' => $status,
                'message' => $message,
            ], $status);
        });
    })->create();
