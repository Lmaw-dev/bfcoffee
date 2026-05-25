<?php
session_start();
// CORS - allow credentials from CRA dev server
$allowed = [
    'http://localhost:3001',
    'http://localhost:3000'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: http://localhost:3001');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

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

function tryLegacyAuth(mysqli $dbConn, string $schemaName, string $identifier, string $password): ?array
{
    $candidateStmt = $dbConn->prepare(
        "SELECT table_name,
                SUM(column_name = 'username') AS has_username,
                SUM(column_name = 'password') AS has_password
         FROM information_schema.columns
         WHERE table_schema = ?
         GROUP BY table_name
         HAVING has_username > 0 AND has_password > 0"
    );

    if (!$candidateStmt) {
        return null;
    }

    $candidateStmt->bind_param('s', $schemaName);
    $candidateStmt->execute();
    $candidates = $candidateStmt->get_result();
    $tables = [];
    while ($row = $candidates->fetch_assoc()) {
        $tableName = strtolower((string)$row['table_name']);
        if ($tableName !== 'staff') {
            $tables[] = $tableName;
        }
    }
    $candidateStmt->close();

    foreach ($tables as $tableName) {
        $columns = [];
        $colResult = $dbConn->query("SHOW COLUMNS FROM `{$tableName}`");
        if (!($colResult instanceof mysqli_result)) {
            continue;
        }

        while ($col = $colResult->fetch_assoc()) {
            $columns[] = strtolower((string)$col['Field']);
        }
        $colResult->free();

        $hasEmail = in_array('email', $columns, true);
        $legacySql = $hasEmail
            ? "SELECT * FROM `{$tableName}` WHERE username = ? OR email = ? LIMIT 1"
            : "SELECT * FROM `{$tableName}` WHERE username = ? LIMIT 1";

        $legacyStmt = $dbConn->prepare($legacySql);
        if (!$legacyStmt) {
            continue;
        }

        if ($hasEmail) {
            $legacyStmt->bind_param('ss', $identifier, $identifier);
        } else {
            $legacyStmt->bind_param('s', $identifier);
        }

        $legacyStmt->execute();
        $legacyResult = $legacyStmt->get_result();

        if ($legacyResult->num_rows !== 1) {
            $legacyStmt->close();
            continue;
        }

        $legacyUser = $legacyResult->fetch_assoc();
        $legacyStmt->close();

        if (!verifyPasswordInput($password, (string)($legacyUser['password'] ?? ''))) {
            return ['success' => false, 'code' => 401, 'message' => 'Invalid credentials'];
        }

        if (isset($legacyUser['active']) && (int)$legacyUser['active'] !== 1) {
            return ['success' => false, 'code' => 403, 'message' => 'Admin account is inactive'];
        }

        $role = (string)($legacyUser['role'] ?? 'Administrator');
        $isAdmin = isset($legacyUser['role']) ? isAdminRole($role) : true;
        if (!$isAdmin) {
            return ['success' => false, 'code' => 403, 'message' => 'Account is valid but not authorized for admin access'];
        }

        $displayName = (string)($legacyUser['name'] ?? ($legacyUser['fullname'] ?? ($legacyUser['username'] ?? 'Administrator')));
        $username = (string)($legacyUser['username'] ?? $identifier);
        $userId = isset($legacyUser['id']) ? (int)$legacyUser['id'] : 0;

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

    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($expectsJson) {
        respond(405, ['success' => false, 'message' => 'Method not allowed'], true);
    }
    header('Location: log-in.html');
    exit();
}

$identifier = trim($_POST['login_id'] ?? ($_POST['email'] ?? ''));
$password = trim($_POST['password'] ?? '');

if ($identifier === '' || $password === '') {
    respond(400, ['success' => false, 'message' => 'Missing credentials'], $expectsJson);
}

require_once __DIR__ . '/db-config.php';

$staffHasEmailColumn = false;
$emailColCheck = $conn->query("SHOW COLUMNS FROM staff LIKE 'email'");
if ($emailColCheck instanceof mysqli_result) {
    $staffHasEmailColumn = $emailColCheck->num_rows > 0;
    $emailColCheck->free();
}

if ($staffHasEmailColumn) {
    $stmt = $conn->prepare("SELECT id, name, role, username, active, password, email FROM staff WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
} else {
    $stmt = $conn->prepare("SELECT id, name, role, username, active, password FROM staff WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $identifier);
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $staff = $result->fetch_assoc();

        $storedPassword = (string)($staff['password'] ?? '');
        if (!verifyPasswordInput($password, $storedPassword)) {
            $stmt->close();
            $conn->close();
            respond(401, ['success' => false, 'message' => 'Invalid credentials'], $expectsJson);
        }

        if ((int)$staff['active'] !== 1) {
            $stmt->close();
            $conn->close();
            respond(403, ['success' => false, 'message' => 'Staff account is inactive'], $expectsJson);
        }

        $_SESSION['staff_username'] = $staff['username'];
        $_SESSION['staff_name'] = $staff['name'];
        $_SESSION['staff_role'] = $staff['role'];
        $_SESSION['staff_id'] = $staff['id'];

        // Admin roles get admin dashboard access.
        $isAdmin = isAdminRole((string)$staff['role']);
        $_SESSION['is_admin'] = $isAdmin;

        if (!$isAdmin) {
            $stmt->close();
            $conn->close();
            respond(403, ['success' => false, 'message' => 'Account is valid but not authorized for admin access'], $expectsJson);
        }

        $stmt->close();
        $conn->close();

        respond(200, [
            'success' => true,
            'message' => 'Login successful',
            'staff' => [
                'id' => (int)$staff['id'],
                'username' => $staff['username'],
                'name' => $staff['name'],
                'role' => $staff['role'],
                'isAdmin' => $isAdmin
            ]
        ], $expectsJson);
    }

    $stmt->close();
}

// Legacy fallback for existing admin records in other schemas/tables
// like: id, fullname, email, username, password.
$schemasToTry = [DB_NAME];
if (strtolower(DB_NAME) !== 'bfs') {
    $schemasToTry[] = 'bfs';
}

foreach ($schemasToTry as $schemaName) {
    $schemaConn = $conn;
    if (strtolower($schemaName) !== strtolower(DB_NAME)) {
        $schemaConn = @new mysqli(DB_HOST, DB_USER, DB_PASS, $schemaName);
        if ($schemaConn->connect_error) {
            continue;
        }
        $schemaConn->set_charset('utf8');
    }

    $legacyAuth = tryLegacyAuth($schemaConn, $schemaName, $identifier, $password);

    if (strtolower($schemaName) !== strtolower(DB_NAME)) {
        $schemaConn->close();
    }

    if ($legacyAuth === null) {
        continue;
    }

    if (empty($legacyAuth['success'])) {
        $conn->close();
        respond((int)$legacyAuth['code'], ['success' => false, 'message' => (string)$legacyAuth['message']], $expectsJson);
    }

    $staffPayload = $legacyAuth['staff'];
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