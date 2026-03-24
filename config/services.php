<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'default' => [
        'xendit' => [
            'key' => [
                'secret' => env('XENDIT_KEY_SECRET'),
                'public' => env('XENDIT_KEY_PUBLIC'),
            ]
        ],
        'callback' => [
            'key' => env('DEFAULT_CALLBACK_KEY'),
            'token'    => env('DEFAULT_CALLBACK_TOKEN'),
            'payment' => env('DEFAULT_PAYMENT_CALLBACK_URL'),
            'shipment' => env('DEFAULT_PAYMENT_CALLBACK_URL'),
        ],
        'frontend' => [
            'url' => env('APP_FRONTEND_URL'),
        ]
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // 'google' => [
    //     'client_id' => env('GOOGLE_CLIENT_ID'),
    //     'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    //     'redirect' => env('GOOGLE_REDIRECT_URI'),
    //     'scopes' => [
    //         'https://www.googleapis.com/auth/userinfo.email',
    //         'https://www.googleapis.com/auth/userinfo.profile',
    //     ],
    // ],
    
    'otp_lifetime' => env('OTP_LIFETIME', 1),
    'resend_otp_lifetime' => env('RESEND_OTP_LIFETIME', 1),

    'firebase' => [
        'credentials' => storage_path('app/firebase/firebase_credentials.json'),
        'url' => env('FIREBASE_URL', 'https://sarinah-ecommerce2025.asia-southeast1.firebasedatabase.app/'),
        'env' => env('FIREBASE_ENV', 'staging')
    ],

    'shipping' => [
        'webhook_secret' => env('WEBHOOK_SECRET', 'sarinah')
    ]
];
