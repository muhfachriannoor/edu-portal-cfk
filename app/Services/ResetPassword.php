<?php

namespace App\Services;


use App\Notifications\PasswordReset as PasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ResetPassword
{
    /**
     * @return View
     */
    public function showForgotForm(): View
    {
        return view('web.forgot-password');
    }

    /**
     * @param Request $request
     * @param string $token
     * @return View
     */
    public function showResetForm(Request $request, string $token): View
    {
        return view('web.reset-password', [
            'token' => $token,
            'email' => $request->input('email')
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $status = $this->broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new PasswordResetNotification($token, $this->guard()));
            }
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => trans($status)
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response()->json([
                'status' => trans($status)
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)]
        ]);
    }

    /**
     * @return PasswordBroker
     */
    protected function broker(): PasswordBroker
    {
        return Password::broker();
    }

    /**
     * @return string
     */
    protected function guard(): string
    {
        return 'web';
    }
}