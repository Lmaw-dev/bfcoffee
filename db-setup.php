<?php
session_start();

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    die('Access Denied. Admin authentication required.');
}

include 'db-config.php';

echo '<h1>Database Setup</h1>';

// Create Registration Table (used by sign up / login)
$sql_registration = "CREATE TABLE IF NOT EXISTS registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    address VARCHAR(255) DEFAULT '',
    sex VARCHAR(20) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_registration) === TRUE) {
    echo '<p style="color: green;">✓ Registration table created or already exists.</p>';
} else {
    echo '<p style="color: red;">✗ Error creating registration table: ' . $conn->error . '</p>';
}

// Create Products Table
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    available BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_products) === TRUE) {
    echo '<p style="color: green;">✓ Products table created or already exists.</p>';
} else {
    echo '<p style="color: red;">✗ Error creating products table: ' . $conn->error . '</p>';
}

// Create Orders Table
$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_date DATETIME NOT NULL,
    items JSON NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    paid DECIMAL(10, 2),
    change_amount DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_orders) === TRUE) {
    echo '<p style="color: green;">✓ Orders table created or already exists.</p>';
} else {
    echo '<p style="color: red;">✗ Error creating orders table: ' . $conn->error . '</p>';
}

// Create Staff Table
$sql_staff = "CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_staff) === TRUE) {
    echo '<p style="color: green;">✓ Staff table created or already exists.</p>';
} else {
    echo '<p style="color: red;">✗ Error creating staff table: ' . $conn->error . '</p>';
}

// Insert default products if table is empty
$check_products = $conn->query("SELECT COUNT(*) as count FROM products");
$result = $check_products->fetch_assoc();

if ($result['count'] == 0) {
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
        $stmt->bind_param("ssdsi", $product[0], $product[1], $product[2], $product[3], $product[4]);
        $stmt->execute();
    }
    $stmt->close();
    echo '<p style="color: green;">✓ Default products inserted.</p>';
}

// Insert default staff if table is empty
$check_staff = $conn->query("SELECT COUNT(*) as count FROM staff");
$result = $check_staff->fetch_assoc();

if ($result['count'] == 0) {
    $default_staff = [
        ['Administrator', 'Manager', 'admin', 'admin123', 1]
    ];

    $stmt = $conn->prepare("INSERT INTO staff (name, role, username, password, active) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($default_staff as $staff) {
        $stmt->bind_param("ssssi", $staff[0], $staff[1], $staff[2], $staff[3], $staff[4]);
        $stmt->execute();
    }
    $stmt->close();
    echo '<p style="color: green;">✓ Default staff inserted.</p>';
}

$conn->close();

echo '<h2>Setup Complete!</h2>';
echo '<p><a href="cafe-admin.php">Go to Admin Dashboard</a></p>';
?>
