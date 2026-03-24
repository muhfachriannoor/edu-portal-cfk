<?php

namespace App\Services;

use App\Models\Otp;
use App\Mail\SendOtp;
use Illuminate\Support\Str;
use App\Events\LogProcessed;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OtpService
{
    /**
     * @param string $identifier
     * @param $email
     * @param $action
     * @param int $digits
     * @return string
     */
    public function generate(string $identifier, $email = null, $action = 'REGISTER', int $digits = 6): string
    {
      
        if ($digits == 5) {
            $token = str_pad($this->generatePin(5), 5, '0', STR_PAD_LEFT);
        } elseif ($digits == 6) {
            $token = str_pad($this->generatePin(6), 6, '0', STR_PAD_LEFT);
        } else {
            $token = str_pad($this->generatePin(), 4, '0', STR_PAD_LEFT);
        }
        $validity = config('services.otp_lifetime');
        $getToken = Otp::updateOrCreate(
            ['identifier' => $identifier],
            ['token' => $token, 'validity' => $validity, 'valid' => true, 'created_at' => now()]
        );
        $token = $getToken->token;
        
        event(new LogProcessed([
            'action' => 'GENERATE_'.$action,
            'identifier' => $identifier,
            'email' => $email,
            'token' => $token,
            'status' => 'SUCCESS'
        ], \App\Models\Log::TYPE_EMAIL));

        return $token;
    }

    /**
     * @param string $identifier
     * @param string $token
     * @param $email
     * @param $action
     * @return object
     */
    public function validate(string $identifier, string $token, $email = null, $action = 'REGISTER') : object
    {
        $otp = Otp::where('identifier', $identifier)
            ->where('token', $token)
            ->first();

        $status = false;
        $message = __('auth.otp_invalid');
        
        if ($otp) {
            if ($otp->valid) {
                $validity = $otp->updated_at->addMinutes($otp->validity);

                if (strtotime($validity) < strtotime(now())) {
                    $message = __('auth.otp_expired');
                } else {
                    $status = true;
                    $message = __('auth.otp_valid');
                }

                $otp->valid = false;
                $otp->save();
            }
        } else {
            if ($masterOTP = Cache::get('otp')) {
                $masterOTP = explode('|', $masterOTP);
                list($masterToken) = $masterOTP;

                if ($masterToken == $token) {
                    $status = true;
                    $message = __('auth.otp_valid');
                }
            }
        }

        event(new LogProcessed([
            'action' => 'VALIDATE_'. $action,
            'identifier' => $identifier,
            'email' => $email,
            'token' => $token,
            'status' => $status ? 'SUCCESS' : 'FAILED'
        ], \App\Models\Log::TYPE_EMAIL));

        return (object) [
            'status' => $status,
            'message' => $message
        ];
    }

    /**
     * @param int $digits
     * @return string
     */
    private function generatePin($digits = 4): string
    {
        $i = 0;
        $pin = '';

        while ($i < $digits) {
            $pin .= mt_rand(0, 9);
            $i++;
        }

        return $pin;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function generateOtp(Request $request): array
    {
        $email = $request->input('email');
        $otpLifetime = (int) config('services.otp_lifetime');
        $expiresIn = $otpLifetime * 60;
        $token = '';
        
        $lock = Cache::lock("generateOtp:{$email}", $expiresIn);

        if ($lock->get()) {
            $identifier = Str::orderedUuid();
            $token = $this->generate($identifier, $email);

            $request->merge([
                'additional_data' => [
                    'agency' => $request->input('agency'),
                    'participant_type' => $request->input('participant_type')
                ]
            ]);

            Cache::put($identifier, $request->except(['agency', 'participant_type', 'token']), 7200);

            $body = "Thank you for registering at Sarinah Official E-Commerce. To activate your account, please enter the following One-Time Password (OTP):";

            Mail::to($email)->queue(new SendOtp($token, 'Your Sarinah Account Verification Code', $body, $email));
            // Notification::route('mail', $email)
            //     ->notify(new SendOTP($token, 'Account Activation', $body));

            Log::channel('horizon')->info('Email sent successfully', [
                'subject'   => 'Your Sarinah Account Verification Code',
                'to'        => $email,
                'token'     => $token,
                'job'       => static::class,
                'time'      => now()->toDateTimeString(),
            ]);

            Cache::put("otpData:{$email}", [
                'identifier' => $identifier,
                'expires_at' => now()->addSeconds($expiresIn)
                    ->toDateTimeString()
            ], $expiresIn);
        } else {
            $lockData = Cache::get("otpData:{$email}");

            if (!is_array($lockData)) {
                optional($lock)->release();
                Cache::lock("generateOtp:{$email}")->forceRelease(); 
                abort(400, 'Ops, Server busy try again later.');
            }

            $identifier = $lockData['identifier'];
            $expiresIn = (int) now()->diffInSeconds(Carbon::parse($lockData['expires_at']), false);
        }

        return [$identifier, $expiresIn, $token];
    }

    /**
     * @param $identifier
     * @return int
     */
    public function regenerateOtp($identifier, $email): int
    {
        if (!Cache::has($identifier)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.identifier'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $resendOtpLifetime = (int) config('services.resend_otp_lifetime');
        $expiresIn = $resendOtpLifetime * 60;

        $lock = Cache::lock("regenerateOTP:{$identifier}", $expiresIn);
        Cache::lock("regenerateOTP:{$identifier}", $expiresIn)->forceRelease();
        if ($lock->get()) {
            $form = Cache::get($identifier);
            $token = $this->generate($identifier, $form['email']);

            $body = "Please enter this Verification code to confirm your email address.";

            Otp::where('identifier', $identifier)->update(['valid' => true]);
            Mail::to($form['email'])->queue(new SendOtp($token, 'Resend Account Activation', $body));
            // Notification::route('mail', $form['email'])
            //     ->notify(new SendOTP($token, 'Resend Account Activation', $body));

            Log::channel('horizon')->info('Email sent successfully', [
                'subject'   => 'Resend Account Activation',
                'to'        => $email,
                'token'     => $token,
                'job'       => static::class,
                'time'      => now()->toDateTimeString(),
            ]);
        } else {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => "Resend OTP can only be done every {$resendOtpLifetime} minutes.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return 300;
    }
}