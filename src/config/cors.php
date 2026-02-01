<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:3000',                        // Frontend React Local
        'http://localhost:8000',                        // Backend Local (jaga-jaga)
        'http://localhost:8080',                        // Backend Local (jaga-jaga port lain)
        env('FRONTEND_URL', 'http://localhost:3000'),   // Alamat dari .env (biasanya Ngrok)
        'https://unitemized-giovanna-centrally.ngrok-free.dev', // Alamat Ngrok Anda (Hardcode biar aman)
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
