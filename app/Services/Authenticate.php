<?php

namespace App\Services;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait Authenticate
{
    /**
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('web.login');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            event(new Lockout($request));

            $seconds = $this->limiter()->availableIn(
                $this->throttleKey($request)
            );

            throw ValidationException::withMessages([
                'email' => [Lang::get('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ])],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($this->attemptLogin($request)) {
            $this->clearLoginAttempts($request);
            return $this->loggedIn($request);
        }

        $this->incrementLoginAttempts($request);

        throw ValidationException::withMessages([
            'email' => [trans('auth.failed')],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    protected function loggedIn(Request $request): JsonResponse
    {
        $request->session()->regenerate();

        return response()->json(null);
    }

    /**
     * @param Request $request
     * @return void
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:8',
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function attemptLogin(Request $request)
    {
        return $this->guard()->attempt(
            $request->only('email', 'password')
        );
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return redirect()->route('web.home');
    }

    /**
     * The user has logged out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function loggedOut(Request $request)
    {
        //
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard($this->guardName());
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function hasTooManyLoginAttempts(Request $request): bool
    {
        return $this->limiter()->tooManyAttempts(
            $this->throttleKey($request), 5
        );
    }

    /**
     * Increment the login attempts for the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function incrementLoginAttempts(Request $request): void
    {
        $this->limiter()->hit(
            $this->throttleKey($request), 10 * 60
        );
    }

    /**
     * Clear the login locks for the given user credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clearLoginAttempts(Request $request): void
    {
        $this->limiter()->clear($this->throttleKey($request));
    }

    /**
     * Get the throttle key for the given request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    /**
     * Get the rate limiter instance.
     *
     * @return \Illuminate\Cache\RateLimiter
     */
    protected function limiter()
    {
        return app(RateLimiter::class);
    }

    /**
     * @return string
     */
    protected function guardName(): string
    {
        return 'web';
    }
}