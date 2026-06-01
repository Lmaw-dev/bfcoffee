<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('Access Denied. Admin authentication required.');
}

include 'db-config.php';

echo '<h1>Database Setup</h1>';

ensureWebSystemSchema($conn);

echo '<p style="color: green;">✓ Core schema checked.</p>';

// Insert default products if table is empty
$result = db_fetch_value($conn, "SELECT COUNT(*) FROM products");

if ((int)$result === 0) {
    $default_products = [
        ['Espresso', 'Coffees', 42.00, 'images/espresso.jpg', 1],
        ['Americano', 'Coffees', 52.50, 'images/americano.jpg', 1],
        ['Cappuccino', 'Coffees', 43.00, 'images/cappuccino.jpg', 1],
        ['Latte', 'Coffees', 33.50, 'images/latte.jpg', 1],
        ['Mocha', 'Coffees', 34.00, 'images/mocha.jpg', 1],
        ['Macchiato', 'Coffees', 32.75, 'images/macchiato.jpg', 1],
        ['Malunggay Pandesal', 'Pastries', 5.00, 'images/pandesal.jpg', 1],
        ['Egg Bread', 'Pastries', 5.00, 'images/egg.jpg', 1],
        ['Pan de Coco', 'Pastries', 5.00, 'images/coco.jpg', 1],
        ['Choco/Vanilla Bavarian', 'Pastries', 10.00, 'images/bavarian.jpg', 1]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, category, price, image, available) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($default_products as $product) {
        $stmt->execute($product);
    }
    echo '<p style="color: green;">✓ Default products inserted.</p>';
}

// Insert default staff if table is empty
$result = db_fetch_value($conn, "SELECT COUNT(*) FROM staff");

if ((int)$result === 0) {
    $default_staff = [
        ['Administrator', 'Manager', 'admin', 'admin123', 1]
    ];

    $stmt = $conn->prepare("INSERT INTO staff (name, role, username, password, active) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($default_staff as $staff) {
        $stmt->execute($staff);
    }
    echo '<p style="color: green;">✓ Default staff inserted.</p>';
}

echo '<h2>Setup Complete!</h2>';
echo '<p><a href="cafe-admin.php">Go to Admin Dashboard</a></p>';
?>
