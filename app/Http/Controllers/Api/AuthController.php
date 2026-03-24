<?php

namespace App\Http\Controllers\Api;

use App\Models\Otp;
use App\Models\User;
use Illuminate\View\View;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\Authenticate;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use App\Notifications\PasswordResetDeeplink;
use App\Services\FirebaseAuthService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @OA\Tag(
 *      name="Auth",
 *      description="User authentication and token management"
 * )
 * 
 * @OA\Schema(
 *      schema="TokenData",
 *      title="Token Data",
 *      description="Structure for Access and Refresh Tokens",
 *      
 *      @OA\Property(property="id", type="integer", example=1),
 *      @OA\Property(property="name", type="string", example="John Doe"),
 *      @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *      @OA\Property(property="is_active", type="boolean", example=true),
 *      @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true, example="2025-01-01T10:00:00.000000Z"),
 *      @OA\Property(property="avatar_url", type="string", nullable=true, example="https://lh3.googleusercontent.com/a/AVni..."),
 *      @OA\Property(property="onboarding_completed", type="boolean", example=false, description="Flag indicating if the user has completed the onboarding flow."),
 * )
 * 
 * @OA\Schema(
 *      schema="AuthResponse",
 *      title="Authentication Response",
 *      
 *      @OA\Property(property="success", type="boolean", example=true),
 *      @OA\Property(property="message", type="string", example="Success login"),
 *      @OA\Property(property="data", type="object",
 *          @OA\Property(property="user", ref="#/components/schemas/TokenData"),
 *          @OA\Property(property="token_type", type="string", example="bearer"),
 *          @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
 *          @OA\Property(property="refresh_token", type="string", example="a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6..."),
 *          @OA\Property(property="expires_in", type="integer", description="Access Token TTL in seconds", example=3600),
 *          @OA\Property(property="refresh_expires_in", type="integer", description="Refresh Token TTL in seconds (e.g., 10 years)", example="315360000"),
 *      )
 * )
 */
class AuthController extends Controller
{
    use Authenticate;

    public $otpService;
    protected FirebaseAuthService $firebaseAuthService;

    /**
     * AuthController constructor.
     */
    public function __construct(FirebaseAuthService $firebaseAuthService)
    {
        $this->otpService = new OtpService;
        $this->firebaseAuthService = $firebaseAuthService;
        
        $this->middleware(['auth:api'])
            ->only(['logout']);
    }
    
    /**
     * Get the guard instance for the API.
     * 
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    /**
     * @return \Illuminate\Support\Facades\Password
     */
    protected function broker()
    {
        return Password::broker('users');
    }

    /**
     * @OA\Post(
     *      path="/auth/login",
     *      operationId="login",
     *      tags={"Auth"},
     *      summary="Log in",
     *      description="Log in with username and password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email", "password"},
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *              @OA\Property(property="password", type="string", example="password")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200, description="Successful social login",
     *          @OA\JsonContent(
     *              ref="#/components/schemas/AuthResponse"
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=429,
     *          description="Too many requests",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Too Many Request.")
     *          )
     *      )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:2'
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->clearLoginAttempts($request);

            throw new HttpResponseException(response()->json([
                'success' => false,
                'email' => [trans('auth.throttle', ['minutes' => 10])],
                'message' => [trans('auth.account_locked', ['minutes' => 10])],
            ], Response::HTTP_TOO_MANY_REQUESTS));
        }

        $credentials = $request->only(['email', 'password']);
        $user = User::where('email', $credentials['email'])->first();

        // If email is not registered
        if(!$user){
            $this->incrementLoginAttempts($request);
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.email_not_registered'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // If email is not verified
        if(is_null($user->email_verified_at)){
            $this->incrementLoginAttempts($request);
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.email_not_verified'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // If success login
        if( $token = $this->attemptApiLogin($request) ){
            $this->clearLoginAttempts($request);
            $user = $this->guard()->user();

            return $this->returnJsonAuthResponse($this->createAuthResponse($user, $token));
        }
        
        // If failed login
        $this->incrementLoginAttempts($request);
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => trans('auth.incorrect_user_password'),
        ], Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    /**
     * @OA\Post(
     *     path="/auth/register",
     *     operationId="register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     description="Registers a new user with name, email, password, and optional phone. Sends email verification.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful",
     *         @OA\JsonContent(
     *             type="object",
     *                @OA\Property(property="success", type="string", example="true"),
    *                 @OA\Property(property="message", type="string", example="Thank you for registering! Please check your email and enter the OTP to complete your verification."),
    *                 @OA\Property(property="identifier", type="string", example="uuid"),
    *                 @OA\Property(property="expires_in", type="integer", example=300)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="success", type="string", example="error"),
     *                 @OA\Property(property="message", type="string", example="Exception message here.")
     *             )
     *         )
     *     )
     * )
     */

    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'email' => [
                'required',
                'email:rfc,dns',
                function ($attribute, $value, $fail) {
                    $blockedDomains = [
                        'yopmail.com',
                        'mailinator.com',
                        'tempmail.com',
                        'guerrillamail.com',
                        '10minutemail.com',
                    ];

                    $domain = strtolower(substr(strrchr($value, "@"), 1));

                    if (in_array($domain, $blockedDomains)) {
                        $fail('Disposable email addresses are not allowed.');
                    }
                },
            ],
        ]);


        DB::beginTransaction();

        try{
            // Check Email
            $user = User::where('email', $request->input('email'))->first();

            if (!$user) {
                // 1. Create new user
                $user = User::create([
                    'email' => $request->input('email')
                ]);
            } else if($user->email_verified_at === null){
                // 2. Do nothing
                $user->update(['email' => $request->input('email')]);
            } else {
                // 3. User is already verified
                DB::rollback();
                 return response()->json([
                    'success' => false,
                    'message' => trans('auth.email_verified')
                ], 400);
            }

            // Generate OTP
            list($identifier, $expiresIn, $token) = $this->otpService->generateOtp($request);

            DB::commit();
            
            $response = [
                'success' => 'true',
                'message' => trans('auth.register_success_check_otp'),
                'identifier' => $identifier,
                'expires_in' => $expiresIn
            ];

            if(in_array(config('app.env'), ['local', 'staging']))
                $response['token'] = $token;

            return response()->json($response);
        } catch(\Exception $e){
            DB::rollback();
            return response()->json([
                'success' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *      path="/auth/refresh-token",
     *      tags={"Auth"},
     *      operationId="refreshToken",
     *      summary="Refreshes the Access Token using a valid Refresh Token",
     * 
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"refresh_token"},
     *              @OA\Property(property="refresh_token", type="string", example="a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...")
     *          )
     *      ),
     * 
     *      @OA\Response(response=200, description="Successful token refresh",
     *          @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *      ),
     * 
     *      @OA\Response(response=401, description="Invalid or expired refresh token")
     * )
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);
        $refreshTokenString = $request->refresh_token;

        $userId = Cache::driver('redis')->get('refresh_token:' . $refreshTokenString);
        $user = User::find($userId);

        if (!$userId) {
            return response()->json(['message' => trans('auth.invalid_refresh_token')], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user) {
            return response()->json(['message' => trans('auth.user_not_found')], Response::HTTP_UNAUTHORIZED);
        }

        Cache::driver('redis')->forget('refresh_token:' . $refreshTokenString);

        return $this->returnJsonAuthResponse($this->createAuthResponse($user));
    }

    /**
     * @OA\Post(
     *     path="/auth/resend-otp/{identifier}",
     *     operationId="resendOtp",
     *     tags={"Auth"},
     *     summary="Resend Verification OTP",
     *     description="Resends the verification email to the specified email address using the provided identifier.",
     *     
     *     @OA\Parameter(
     *         name="identifier",
     *         in="path",
     *         required=true,
     *         description="Unique identifier (UUID)",
     *         @OA\Schema(type="string", example="9f854b8c-e850-46d6-acfc-b1dceb2a3904")
     *     ),
     *     
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", example="john@example.com")
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="A new OTP has been sent to your email.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="string", example="true"),
     *             @OA\Property(property="message", type="string", example="A new OTP has been sent to your email."),
     *             @OA\Property(property="identifier", type="string", example="uuid"),
     *             @OA\Property(property="expires_in", type="integer", example=300)
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized or already verified",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="Email is not registered")
     *                 ),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", example="Email has been verified.")
     *                 )
     *             }
     *         )
     *     )
     * )
     */

    public function resend(Request $request, $identifier): JsonResponse
    {
        $email = $request->input('email');
        $lockKey = "resendOtp:{$email}";
        $lockTtl = ( (int) config('services.resend_otp_lifetime') ) * 60;

        // Check existing lock time
        $lockData = Cache::get($lockKey . ':meta');

        $lock = Cache::lock($lockKey, $lockTtl);

        if (!$lock->get()) {
            // if still locked, show countdown
            if ($lockData && isset($lockData['expires_at'])) {
                $remaining = (int) now()->diffInSeconds(Carbon::parse($lockData['expires_at']), false);
            } else {
                $remaining = $lockTtl;
            }

            return response()->json([
                'success' => false,
                'message' => trans('auth.resend_wait_request', ['seconds' => 10]),
                'remaining_seconds' => max(0, $remaining)
            ]);
        }

        // ✅ Save lock metadata (for countdown)
        Cache::put($lockKey . ':meta', [
            'expires_at' => now()->addSeconds($lockTtl)->toDateTimeString()
        ], $lockTtl);

        // continue your existing logic...
        $user = User::where('email', $email)->first();
        $otp = Otp::where('identifier', $identifier)->first();

        if (!$otp) return $this->error('Identifier not registered.');
        if (!$user) return $this->error('Email not registered.');
        if ($user->email_verified_at) return $this->error('Email has already been verified.');

        $expiresIn = $this->otpService->regenerateOtp($identifier, $user->email);

        return response()->json([
            'success' => true,
            'message' => trans('auth.new_otp'),
            'identifier' => $identifier,
            'expires_in' => $expiresIn
        ]);
    }


    /**
     * @param Request $request
     * @param string $identifier
     * @return JsonResponse
     * @throws ValidationException
     *
     * @OA\Post(
     *      path="/auth/verify/{identifier}",
     *      operationId="verifyOtp",
     *      tags={"Auth"},
     *      summary="Otp form verify",
     *      description="Otp form verify",
     *      @OA\Parameter(
     *          name="X-Localization",
     *          required=false,
     *          in="header",
     *          description="Localization response message",
     *          @OA\Schema(type="string", enum={"en", "id"}, default="en")
     *       ),
     *      @OA\Parameter(
     *          name="identifier",
     *          description="Identifier",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"token"},
     *              @OA\Property(property="token", type="integer", example="1234")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="identifier", type="string", example="MTppdHNtZWFiZGVAZ21haWwuY29t"),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="cart_id", type="integer", format="int64", example=1, description="Cart ID"),
     *                  @OA\Property(
     *                      property="item_ids",
     *                      type="array",
     *                      description="CartItems ID",
     *                      @OA\Items(type="integer", format="int64", example=1)
     *                  ),
     *              ),
     *          )
     *       ),
     *     @OA\Response(
     *          response=400,
     *          description="Invalid identifier",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="The identifier is invalid."),
     *          )
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Form input errors",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="The given data was invalid."),
     *              @OA\Property(
     *                  property="errors",
     *                  type="object",
     *                  @OA\Property(
     *                      property="token",
     *                      type="array",
     *                      @OA\Items(type="string", default="The token is invalid.")
     *                  )
     *              )
     *          )
     *      ),
     * )
     */
    public function verify(Request $request, string $identifier): JsonResponse
    {
        $request->validate(['token' => 'required|numeric']);

        if (!Cache::has($identifier)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.identifier'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        if ($this->hasTooManyLoginAttempts($request)) {
            event(new Lockout($request));

            $seconds = $this->limiter()->availableIn(
                $this->throttleKey($request)
            );

            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.throttle'),
                'token' => [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]
            ], Response::HTTP_TOO_MANY_REQUESTS));
        }

        $form = Cache::get($identifier);
        $otp = $this->otpService->validate($identifier, $request->token, $form['email']);

        if (!$otp->status) {
            $this->incrementLoginAttempts($request);

            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => trans('auth.token'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $user = User::where('email', $form['email'])->first();
        $user->email_verified_at = now();
        $user->is_active = 1;
        $user->save();

        Cache::forget($identifier);
        $this->clearLoginAttempts($request);

        Cache::put("setPassword:{$user->email}", true, now()->addDays(1));
        return response()->json([
            'success' => true,
            'message' => trans('auth.verify_success')
        ]);
    }

    /**
     * @OA\Post(
     *      path="/auth/forgot-password",
     *      operationId="forgot-password",
     *      tags={"Auth"},
     *      summary="Forgot password",
     *      description="Request email to reset password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email"},
     *              @OA\Property(property="email", type="string", example="john@example.com")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="We have emailed your password reset link!")
     *          )
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unprocessable!")
     *          )
     *      ),
     *      @OA\Response(
     *          response=429,
     *          description="Too Many Request",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="You can request password reset only once every 1 minute. Please wait")
     *          )
     *      )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $email = strtolower($request->input('email'));
        $cacheKey = 'forgot_password:' . md5($email); // avoid key injection

        if (Cache::has($cacheKey)) {
            throw new HttpResponseException(response()->json([
                'status' => false,
                'message' => trans('auth.request_password')
            ], 429));
        }

        // Set cache key to expire in 60 seconds
        Cache::put($cacheKey, true, now()->addMinutes(1));
        
        $response = $this->broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) use($request){
                $email = $request->input('email');
                $locale = app()->getLocale();

                Log::info("Throttled forgot password attempt: {$request->input('email')} from {$request->ip()}");

                // $user->notify(new PasswordReset($token, 'api') );
                $user->notify(new PasswordResetDeeplink($token, $email, $locale));
            }
        );

        if ($response == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => true,
                'message' => trans($response)
            ]);
        }

        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => trans($response, ['minutes' => 1])
        ], 422));
    }

    /**
     * @param Request $request
     * @param null $token
     * @return View
     */
    public function resetPassword(Request $request): View
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Password::tokenExists($user, $request->token)) {
            return view('api.token-expired'); // or return a JSON error, or redirect
        }
        
        return view('api.reset-password')->with(
            ['token' => $request->token, 'email' => $request->email]
        );
    }

    /**
     * @OA\Post(
     *      path="/auth/set-password",
     *      operationId="set-password",
     *      tags={"Auth"},
     *      summary="Set new password",
     *      description="Submit form to save new password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"password", "password_confirmation", "token"},
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *              @OA\Property(property="password", type="string", example="password"),
     *              @OA\Property(property="password_confirmation", type="string", example="password"),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="1"),
     *              @OA\Property(property="message", type="string", example="Your password has been set!")
     *          )
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Unprocessable!")
     *          )
     *      )
     * )
     */
    public function setPassword(Request $request)
    {
        if(Cache::has("setPassword:{$request->input('email')}")){
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:2|confirmed',
            ]);
            
            $user = User::where('email', $request->input('email'))->update([
                'password' => Hash::make($request->input('password'))
            ]);

            // Delete cache after save password
            Cache::forget("setPassword:{$request->input('email')}");

            // If success login
            if( $token = $this->attemptApiLogin($request) ){
                $this->clearLoginAttempts($request);
                $user = $this->guard()->user();

                return $this->returnJsonAuthResponse($this->createAuthResponse($user, $token));
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => trans('auth.set_password_expired')
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/check-reset-token",
     *     operationId="checkResetPasswordToken",
     *     tags={"Auth"},
     *     summary="Check reset password token validity",
     *     description="Verify whether a password reset token is valid, not expired, and matches the given email.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","token"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@example.com"
     *             ),
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 example="f8a9c2d7a4b6e1c3"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Token is valid",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token is valid")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid token")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 example={"email": {"The email field is required."}}
     *             )
     *         )
     *     )
     * )
     */

    public function checkResetToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $record = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token'
            ], 400);
        }

        // Check token hash
        if (!Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 400);
        }

        // Check expiration
        $expiresAt = Carbon::parse($record->created_at)
            ->addMinutes(config('auth.passwords.users.expire'));

        if (now()->greaterThan($expiresAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token is valid'
        ]);
    }

    /**
     * @OA\Post(
     *      path="/auth/reset-password",
     *      operationId="reset-password",
     *      tags={"Auth"},
     *      summary="Reset password, integrate with forgot password",
     *      description="Submit form to update new password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email", "password", "password_confirmation", "token"},
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *              @OA\Property(property="password", type="string", example="password"),
     *              @OA\Property(property="password_confirmation", type="string", example="password"),
     *              @OA\Property(property="token", type="string", example="510c691c354fd9f7608f85171e32b5c87e")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="status", type="string", example="1"),
     *              @OA\Property(property="message", type="string", example="Your password has been reset!")
     *          )
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Content",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Unprocessable!")
     *          )
     *      )
     * )
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|min:2|confirmed'
        ]);

        $response = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->password = Hash::make($password);
                // $user->setRememberToken(Str::random(60));
                $user->save();
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            return response()->json(['status' => true, 'message' => trans($response)]);
        }

        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => trans($response)
        ], 422));
    }

    /**
     * @return JsonResponse
     *
     * @OA\Delete(
     *      path="/auth/logout",
     *      operationId="authLogout",
     *      tags={"Auth"},
     *      summary="Logout",
     *      description="Logged out",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="X-Localization",
     *          required=false,
     *          in="header",
     *          description="Localization response message",
     *          @OA\Schema(type="string", enum={"en", "id"}, default="en")
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="OK",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Successfully logged out.")
     *          )
     *       ),
     *     @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *       ),
     * )
     */
    public function logout(): JsonResponse
    {
        $guard = $this->guard();

        $guard->logout();

        return response()->json([
            'message' => trans('message.logout')
        ]);
    }

    /**
     * @OA\Post(
     *      path="/auth/firebase-login",
     *      operationId="firebaseLogin",
     *      tags={"Auth"},
     *      summary="Login using Firebase ID Token (Google/Firebase Sign-In)",
     *      description="Authenticate user by verifying a Firebase ID Token obtained from Firebase Auth on the frontend. Returns the same token structure as the normal login endpoint.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"firebase_token"},
     *              @OA\Property(
     *                  property="firebase_token",
     *                  type="string",
     *                  description="Firebase ID Token returned by Firebase Auth",
     *                  example="eyJhbGciOiJSUzI1NiIsImtpZCI6IjU4Z..."
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful Firebase login",
     *          @OA\JsonContent(ref="#/components/schemas/AuthResponse")
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Invalid or expired Firebase ID token",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Invalid or expired Firebase ID token.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Missing firebase_token or token does not contain required claims (e.g. email)",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="The firebase_token field is required.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=403,
     *          description="User account is inactive / blocked",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Your account is inactive. Please contact support.")
     *          )
     *      )
     * )
     *
     * Login using Firebase ID Token (Google Sign-In via Firebase).
     *
     * Expected JSON payload:
     *  {
     *      "firebase_token": "<FIREBASE_ID_TOKEN>"
     *  }
     */
    public function firebaseLogin(Request $request): JsonResponse
    {
        // 0. Basic validation for input
        $request->validate([
            'firebase_token' => 'required|string',
        ]);

        $idToken = trim($request->input('firebase_token', ''));

        if ($idToken === '') {
            return response()->json([
                'success' => false,
                'message' => 'Firebase ID token is empty.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 1. Verify Firebase ID Token
        $verifiedToken = $this->firebaseAuthService->verifyIdToken($idToken);

        if ($verifiedToken === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Firebase ID token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 2. Extract claims / user info from token
        $firebaseUser = $this->firebaseAuthService->extractUserInfo($verifiedToken);

        $email         = $firebaseUser['email'] ?? null;
        $name          = $firebaseUser['name'] ?? null;
        $avatarUrl     = $firebaseUser['picture'] ?? null;
        $emailVerified = $firebaseUser['email_verified'] ?? false;
        $firebaseUid   = $firebaseUser['uid'] ?? null;

        if (!$email) {
            // Your system strongly relies on email as identifier
            return response()->json([
                'success' => false,
                'message' => 'Email is missing from Firebase token.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3. Find or create local user by email
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if ($user) {
            // If user is inactive, block login
            if ((int) $user->is_active === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact support.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Update social login-related fields
            $user->provider   = 'firebase_google';
            $user->google_id  = $firebaseUid ?: $user->google_id;
            $user->avatar_url = $avatarUrl ?: $user->avatar_url;

            // Auto-verify email if Firebase says it is verified
            if ($emailVerified && $user->email_verified_at === null) {
                $user->email_verified_at = now();
            }

            $user->save();
        } else {
            // 4. Create new user for the first-time Firebase login
            $user = User::create([
                'name'              => $name,
                'email'             => $email,
                'google_id'         => $firebaseUid,
                'provider'          => 'firebase_google',
                'avatar_url'        => $avatarUrl,
                'is_active'         => 1,
                'email_verified_at' => $emailVerified ? now() : null,
                // Password is null for social/Firebase users
            ]);
        }

        // 5. Generate JWT + refresh token like normal login
        $tokens = $this->createAuthResponse($user);

        return $this->returnJsonAuthResponse($tokens);
    }
    
    /**
     * @param LoginRequest $request
     * @return mixed
     */
    protected function attemptApiLogin($request)
    {
        $request->merge(['is_active' => 1]);
        return $this->guard()->attempt(
            $request->only('email', 'password', 'is_active')
        );
    }

    /**
     * Creates the complete authentication response, including Access Token, Refresh Token,
     * and structured user data. This is the single source of truth for token generation.
     * * NOTE: The return type is changed from JsonResponse to array to accommodate the redirect logic.
     * 
     * @param User $user The authenticated user model.
     * @param string|null $existingAccessToken An existing JWT if already generated (e.g., from guard()->attempt()).
     * @return array
     */
    protected function createAuthResponse(User $user, ?string $existingAccessToken = null): array
    {
        // 1. Create Access Token (JWT)
        // Use existing token (from login) or generate a new one (for refresh/social login)
        $accessToken = $existingAccessToken ?? JWTAuth::fromUser($user);

        // 2. Create Refresh Token (unique string, set 10 years TTL)
        $refreshTokenString = hash('sha256', $accessToken . microtime() . $user->id);
        $refreshTokenTTLMinutes = 10 * 365 * 24 * 60; // 10 years
        $refreshTokenTTLSeconds = $refreshTokenTTLMinutes * 60;

        // 3. Store Refresh Token in Cache/Redis
        Cache::driver('redis')->put('refresh_token:'. $refreshTokenString, $user->id, $refreshTokenTTLSeconds);

        // 4. Compile the full response array
        $tokens = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'avatar_url' => $user->avatar_url,
                'onboarding_completed' => (bool) $user->onboarding_completed,
            ],
            'token_type' => 'bearer',
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenString,
            'expires_in' => config('jwt.ttl') * 60, // Access Token TTL (from JWT config)
            'refresh_expires_in' => $refreshTokenTTLSeconds, // Refresh Token TTL (10 years)
        ];

        // 5. Return the array data (TIDAK LAGI JSON RESPONSE)
        return $tokens;
    }

    /**
     * Helper to return the final JSON response structure for authentication methods.
     * This is used by login, refreshToken, and setPassword.
     * 
     * @param array $tokens The token data array from createAuthResponse().
     * @return JsonResponse
     */
    protected function returnJsonAuthResponse(array $tokens): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => trans('auth.success_login'),
            'data' => $tokens
        ], Response::HTTP_OK);
    }

}