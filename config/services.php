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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | API detektor AI eksternal (opsional). Bila enabled=true & url terisi,
    | AiDetectionService memakai API ini untuk analisis teks; jika gagal/kosong
    | otomatis fallback ke heuristik lokal. Butuh internet di server.
    | Contoh provider: GPTZero, Sapling, Originality.ai, Winston AI, ZeroGPT.
    */
    'ai_detector' => [
        'enabled' => env('AI_DETECTOR_ENABLED', false),
        'name' => env('AI_DETECTOR_NAME', 'external-api'),
        'url' => env('AI_DETECTOR_URL'),
        'key' => env('AI_DETECTOR_KEY'),
        'text_field' => env('AI_DETECTOR_TEXT_FIELD', 'text'),
        'response_path' => env('AI_DETECTOR_RESPONSE_PATH', 'ai_percentage'),
        'scale' => env('AI_DETECTOR_SCALE', 1), // 1 jika respons 0-100; 100 jika 0-1
    ],

];
