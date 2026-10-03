<?php

declare(strict_types=1);

$googleOauth = [];
$googlePath = __DIR__ . '/google.php';
if (is_file($googlePath)) {
    $loaded = require $googlePath;
    if (is_array($loaded)) {
        $googleOauth = $loaded;
    }
}

$secrets = [];
$secretsPath = __DIR__ . '/secrets.php';
if (is_file($secretsPath)) {
    $loadedSecrets = require $secretsPath;
    if (is_array($loadedSecrets)) {
        $secrets = $loadedSecrets;
    }
}

return [
    'db' => [
        'host' => 'sql104.infinityfree.com',
        'name' => 'if0_42266462_nutrify',
        'user' => 'if0_42266462',
        'pass' => 'ODlqkgjyHbEbER',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => 'https://nutrify.freehosting.dev',
    ],
    'google_oauth' => [
        'client_id' => (string)($googleOauth['client_id'] ?? ''),
        'client_secret' => (string)($googleOauth['client_secret'] ?? ''),
        'redirect_uri' => (string)($googleOauth['redirect_uri'] ?? 'https://nutrify.freehosting.dev/public/login_auth/oauth2callback.php'),
    ],
    'mail' => [
        'driver' => 'smtp',
        'from_email' => 'dadongalfanta9182@gmail.com',
        'from_name' => 'Nutrify',
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'dadongalfanta9182@gmail.com',
            'password' => 'yhahfllmoxdvedia',
            'encryption' => 'tls',
            'debug' => 0,
        ],
    ],
    'usda' => [
        'api_key' => (string)($secrets['usda_api_key'] ?? getenv('USDA_API_KEY') ?: ''),
    ],
    'gemini' => [
        'api_key' => (string)($secrets['gemini_api_key'] ?? getenv('GEMINI_API_KEY') ?: ''),
        'model'   => 'gemini-1.5-flash',
    ],
];
