<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi CORS untuk VBAT-PONSEL Backend API.
    | Mengizinkan request dari Flutter Web (CanvasKit) dan Mobile App.
    |
    | Dokumentasi: docs/CORS_CONFIG.md
    | Phase: 0.6 — TASK-M1-BE-01
    |
    */

    /*
    |----------------------------------------------------------------------
    | Allowed Origins (Origins yang Diizinkan)
    |----------------------------------------------------------------------
    |
    | Daftar origin yang boleh mengakses API ini.
    | Gunakan '*' untuk mengizinkan semua origin (hanya untuk development).
    | Untuk production, sebaiknya batasi ke domain Flutter Web & Admin Panel.
    |
    | Contoh:
    |   ['http://localhost:3000', 'https://web.vbat.id', 'https://admin.vbat.id']
    |
    */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
