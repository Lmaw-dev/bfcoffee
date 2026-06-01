<?php
/**
 * Simple migration: converts any non-hashed passwords in `staff.password` into bcrypt hashes.
 * Run once from CLI or browser (CLI recommended): php migrate-hash-passwords.php
 */
require_once __DIR__ . '/db-config.php';

echo "Starting staff password migration...\n";

$res = $conn->query("SELECT id, password FROM staff");
$updated = 0;
foreach (db_fetch_all($conn, "SELECT id, password FROM staff") as $row) {
    $id = (int)$row['id'];
    $pw = (string)$row['password'];
    // Heuristic: assume hashed if it starts with common algo prefixes
    if (preg_match('/^\$2[aby]\$|^\$argon2/', $pw)) {
        continue;
    }
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE staff SET password = ? WHERE id = ?");
    if ($stmt->execute([$hash, $id])) {
        $updated++;
    }
}

echo "Migration complete. Passwords hashed for {$updated} staff rows.\n";
$conn->close();
?>
