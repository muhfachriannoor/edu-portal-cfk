<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed for validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated user redirect per guard.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $guard = $exception->guards()[0] ?? null;

        switch ($guard) {
            case 'admin':
                $login = 'admin.login';
                break;

            case 'gate_staff':
                $login = 'gate_staff.login';
                break;

            case 'queue_staff':
                $login = 'queue_staff.login';
                break;

            default:
                $login = 'login'; // normal user
                break;
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Unauthenticated.'], 401)
            : redirect()->route($login);
    }
}
