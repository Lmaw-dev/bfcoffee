<?php
require_once __DIR__ . '/db-helpers.php';

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', (int)(getenv('DB_PORT') ?: 0));
}

if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'web_system');
}

if (!defined('DB_DRIVER')) {
    define('DB_DRIVER', getenv('DB_DRIVER') ?: '');
}

function parseDatabaseUrl(string $url): array
{
    $parts = parse_url($url);
    if ($parts === false) {
        throw new RuntimeException('Invalid database connection URL');
    }

    return [
        'host' => (string)($parts['host'] ?? 'localhost'),
        'port' => isset($parts['port']) ? (int)$parts['port'] : null,
        'dbname' => isset($parts['path']) ? ltrim((string)$parts['path'], '/') : '',
        'user' => isset($parts['user']) ? rawurldecode((string)$parts['user']) : '',
        'pass' => isset($parts['pass']) ? rawurldecode((string)$parts['pass']) : '',
    ];
}

function createDatabaseConnection(): PDO
{
    $databaseUrl = getenv('DATABASE_URL') ?: getenv('SUPABASE_DB_URL') ?: getenv('DB_URL') ?: '';
    $driver = strtolower(getenv('DB_DRIVER') ?: ($databaseUrl !== '' ? 'pgsql' : 'mysql'));

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if ($driver === 'pgsql') {
        $config = $databaseUrl !== ''
            ? parseDatabaseUrl($databaseUrl)
            : [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => (int)(getenv('DB_PORT') ?: 5432),
                'dbname' => getenv('DB_NAME') ?: 'postgres',
                'user' => getenv('DB_USER') ?: 'postgres',
                'pass' => getenv('DB_PASS') ?: '',
            ];

        $port = (int)($config['port'] ?? 5432);
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
            $config['host'],
            $port,
            $config['dbname']
        );

        return new PDO($dsn, $config['user'], $config['pass'], $options);
    }

    $host = getenv('DB_HOST') ?: 'localhost';
    $port = (int)(getenv('DB_PORT') ?: 3306);
    $dbname = getenv('DB_NAME') ?: 'web_system';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

    return new PDO($dsn, $user, $pass, $options);
}

try {
    $conn = createDatabaseConnection();
} catch (Throwable $exception) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $exception->getMessage()]));
}

require_once __DIR__ . '/schema-bootstrap.php';
ensureWebSystemSchema($conn);

