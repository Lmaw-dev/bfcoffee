<?php
session_start();
require_once __DIR__ . '/cors.php';

bfcApplyCorsHeaders(true);
bfcHandleCorsPreflight();

$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
$expectsJson = $isAjax || stripos($acceptHeader, 'application/json') !== false;

function respond(int $statusCode, array $payload, bool $expectsJson): void
{
    http_response_code($statusCode);

    if ($expectsJson) {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit();
    }

    $success = !empty($payload['success']);
    if ($success) {
        header('Location: cafe-admin.php');
        exit();
    }

    $message = urlencode((string)($payload['message'] ?? 'Login failed'));
    header("Location: log-in.html?error={$message}");
    exit();
}

function isAdminRole(string $role): bool
{
    $normalized = strtolower(trim($role));
    if ($normalized === '') {
        return false;
    }

    $knownAdminRoles = ['manager', 'admin', 'administrator', 'owner', 'super admin', 'superadmin'];
    if (in_array($normalized, $knownAdminRoles, true)) {
        return true;
    }

    return str_contains($normalized, 'admin') || str_contains($normalized, 'manager');
}

function verifyPasswordInput(string $inputPassword, string $storedPassword): bool
{
    if ($storedPassword === '') {
        return false;
    }

    return hash_equals($storedPassword, $inputPassword) || password_verify($inputPassword, $storedPassword);
}

function tableExists(PDO $conn, string $tableName): bool
{
    $schemaName = db_is_postgres($conn) ? 'public' : DB_NAME;
    $exists = db_fetch_value(
        $conn,
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
        [$schemaName, $tableName]
    );

    return (int)($exists ?? 0) > 0;
}

function hasColumn(PDO $conn, string $tableName, string $columnName): bool
{
    $schemaName = db_is_postgres($conn) ? 'public' : DB_NAME;
    $exists = db_fetch_value(
        $conn,
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
        [$schemaName, $tableName, $columnName]
    );

    return (int)($exists ?? 0) > 0;
}

function fetchLoginCandidate(PDO $conn, string $tableName, string $identifier): ?array
{
    if (!tableExists($conn, $tableName)) {
        return null;
    }

    $hasEmail = hasColumn($conn, $tableName, 'email');
    $sql = $hasEmail
        ? "SELECT * FROM {$tableName} WHERE username = ? OR email = ? LIMIT 1"
        : "SELECT * FROM {$tableName} WHERE username = ? LIMIT 1";
    $params = $hasEmail ? [$identifier, $identifier] : [$identifier];

    return db_fetch_one($conn, $sql, $params);
}

function buildLoginResponse(array $record, string $identifier, string $password): ?array
{
    if (!verifyPasswordInput($password, (string)($record['password'] ?? ''))) {
        return ['success' => false, 'code' => 401, 'message' => 'Invalid credentials'];
    }

    if (isset($record['active']) && (int)$record['active'] !== 1) {
        return ['success' => false, 'code' => 403, 'message' => 'Admin account is inactive'];
    }

    $role = (string)($record['role'] ?? 'Administrator');
    if (!isAdminRole($role) && !empty($record['role'])) {
        return ['success' => false, 'code' => 403, 'message' => 'Account is valid but not authorized for admin access'];
    }

    $displayName = (string)($record['name'] ?? ($record['fullname'] ?? ($record['username'] ?? 'Administrator')));
    $username = (string)($record['username'] ?? $identifier);
    $userId = isset($record['id']) ? (int)$record['id'] : 0;

    return [
        'success' => true,
        'staff' => [
            'id' => $userId,
            'username' => $username,
            'name' => $displayName,
            'role' => $role,
            'isAdmin' => true
        ]
    ];
}

$candidates = ['staff', 'users'];
foreach ($candidates as $tableName) {
    $record = fetchLoginCandidate($conn, $tableName, $identifier);
    if ($record === null) {
        continue;
    }

    $result = buildLoginResponse($record, $identifier, $password);
    if ($result === null) {
        continue;
    }

    if (empty($result['success'])) {
        $conn->close();
        respond((int)$result['code'], ['success' => false, 'message' => (string)$result['message']], $expectsJson);
    }

    $staffPayload = $result['staff'];
    $_SESSION['staff_username'] = $staffPayload['username'];
    $_SESSION['staff_name'] = $staffPayload['name'];
    $_SESSION['staff_role'] = $staffPayload['role'];
    $_SESSION['staff_id'] = $staffPayload['id'];
    $_SESSION['is_admin'] = true;

    $conn->close();
    respond(200, [
        'success' => true,
        'message' => 'Login successful',
        'staff' => $staffPayload
    ], $expectsJson);
}

$conn->close();
respond(401, ['success' => false, 'message' => 'Invalid credentials'], $expectsJson);
?>