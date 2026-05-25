<?php
/**
 * Simple migration: converts any non-hashed passwords in `staff.password` into bcrypt hashes.
 * Run once from CLI or browser (CLI recommended): php migrate-hash-passwords.php
 */
require_once __DIR__ . '/db-config.php';

echo "Starting staff password migration...\n";

$res = $conn->query("SELECT id, password FROM staff");
if (!$res) {
    echo "Failed to read staff table: " . $conn->error . "\n";
    exit(1);
}

$updated = 0;
while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id'];
    $pw = (string)$row['password'];
    // Heuristic: assume hashed if it starts with common algo prefixes
    if (preg_match('/^\$2[aby]\$|^\$argon2/', $pw)) {
        continue;
    }
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE staff SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $id);
    if ($stmt->execute()) $updated++;
    $stmt->close();
}

echo "Migration complete. Passwords hashed for {$updated} staff rows.\n";
$conn->close();
?>
