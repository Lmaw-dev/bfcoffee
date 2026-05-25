<?php
function ensureWebSystemSchema(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS registration (
        id INT AUTO_INCREMENT PRIMARY KEY,
        firstname VARCHAR(100) NOT NULL,
        lastname VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        address VARCHAR(255) DEFAULT '',
        sex VARCHAR(20) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(10, 2) NOT NULL,
        image VARCHAR(255),
        available BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_date DATETIME NOT NULL,
        items JSON NOT NULL,
        total DECIMAL(10, 2) NOT NULL,
        paid DECIMAL(10, 2),
        change_amount DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS staff (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        role VARCHAR(50) NOT NULL,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        active BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS cafe_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(120) NOT NULL UNIQUE,
        setting_value LONGTEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $countProducts = (int)$conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
    if ($countProducts === 0) {
        $conn->query("INSERT INTO products (name, category, price, image, available) VALUES
            ('Espresso', 'Coffees', 42.00, 'images/espresso.jpg', 1),
            ('Americano', 'Coffees', 52.50, 'images/americano.jpg', 1),
            ('Cappuccino', 'Coffees', 43.00, 'images/cappuccino.jpg', 1),
            ('Latte', 'Coffees', 33.50, 'images/latte.jpg', 1),
            ('Mocha', 'Coffees', 34.00, 'images/mocha.jpg', 1),
            ('Macchiato', 'Coffees', 32.75, 'images/macchiato.jpg', 1),
            ('Malunggay Pandesal', 'Pastries', 5.00, 'images/pandesal.jpg', 1),
            ('Egg Bread', 'Pastries', 5.00, 'images/egg.jpg', 1),
            ('Pan de Coco', 'Pastries', 5.00, 'images/coco.jpg', 1),
            ('Choco/Vanilla Bavarian', 'Pastries', 10.00, 'images/bavarian.jpg', 1)
        ");
    }

    $countStaff = (int)$conn->query("SELECT COUNT(*) AS c FROM staff")->fetch_assoc()['c'];
    if ($countStaff === 0) {
        $stmt = $conn->prepare("INSERT INTO staff (name, role, username, password, active) VALUES (?, ?, ?, ?, ?)");
        $name = 'Administrator';
        $role = 'Manager';
        $username = 'admin';
        $password = 'admin123';
        // store hashed password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $active = 1;
        $stmt->bind_param('ssssi', $name, $role, $username, $passwordHash, $active);
        $stmt->execute();
        $stmt->close();
    }
}
?>
