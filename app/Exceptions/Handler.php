<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that are not reported.
     */
    protected $dontReport = [];

    /**
     * A list of inputs that are never flashed for validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Customize the unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // dd("unauthenticated 42",$request, $exception);
        // if ($exception instanceof AuthorizationException) {
            // Force JSON response for API
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Please provide a valid API token.',
                ], 401);
            }
        // }

        return Redirect::guest(route('login'));
    }
}
