<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Token\VerifiedToken;
use Illuminate\Support\Facades\Log;

class FirebaseAuthService
{
    /** @var FirebaseAuth */
    protected FirebaseAuth $auth;

    public function __construct()
    {
        // Create Firebase Auth instance using service account credentials
        $factory = (new Factory)
            ->withServiceAccount(config('services.firebase.credentials'));

        $this->auth = $factory->createAuth();
    }

    /**
     * Verify a Firebase ID token.
     * 
     * @param string $idToken
     * @return VerifiedToken|null
     */
    public function verifyIdToken(string $idToken)
    {
        try {
            return $this->auth->verifyIdToken($idToken, $checkRevoked = false, $leewayInSeconds = 300);
        } catch (\Throwable $e) {
            // Do NOT expose full error to client, just log internally
            Log::warning('Firebase ID token verification failed', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract useful user info from VerifiedToken claims.
     * 
     * @param VerifiedToken $token
     * @return array{
     *  uid: string|null,
     *  email: ?string,
     *  email_verified: bool,
     *  name: ?string,
     *  picture: ?string
     * }
     */
    public function extractUserInfo($token): array
    {
        // Both VerifiedToken and Lcobucci Plain should have claims()->get()
        $claims = $token->claims();
        $uid = $claims->get('user_id') ?? $claims->get('sub');

        return [
            'uid'            => $uid,
            'email'          => $claims->get('email'),
            'email_verified' => (bool) $claims->get('email_verified', false),
            'name'           => $claims->get('name'),
            'picture'        => $claims->get('picture'),
        ];
    }
}