<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function render($request, \Throwable $exception)
    {
        // Check if the exception is an UnauthorizedHttpException or JWTException
        if ($exception instanceof UnauthorizedHttpException) {
            return response()->json([
                'error' => 'Unauthorized. Invalid or missing token.'
            ], 401);
        }

        if ($exception instanceof JWTException) {
            return response()->json([
                'error' => 'Token is invalid or expired.'
            ], 401);
        }

        return parent::render($request, $exception);
    }
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
