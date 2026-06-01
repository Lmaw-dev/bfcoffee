<?php

function bfcAllowedOrigins(): array
{
    $configuredOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: getenv('FRONTEND_ORIGIN') ?: '';

    if (trim($configuredOrigins) === '') {
        return [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'https://bfcoffee-6ogh.vercel.app',
            'https://bfcoffee.vercel.app'
        ];
    }

    $origins = array_map('trim', explode(',', $configuredOrigins));
    $origins = array_values(array_filter($origins, static fn(string $origin): bool => $origin !== ''));

    return $origins;
}

function bfcApplyCorsHeaders(bool $allowCredentials = true): void
{
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $allowedOrigins = bfcAllowedOrigins();

    if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }
    } elseif (!empty($allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
        if ($allowCredentials) {
            header('Access-Control-Allow-Credentials: true');
        }
    }

    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
}

function bfcHandleCorsPreflight(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}
