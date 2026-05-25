<?php
session_start();

// CORS for development - allow credentials from dev server
$allowed = [
    'http://localhost:3000'
    
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Credentials: true');
    }
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include 'db-config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    // ════════════════════════════════════════════════
    // STORAGE SETUP
    // ════════════════════════════════════════════════
    case 'init_storage':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }

        ensureWebSystemSchema($conn);

        $tables = ['registration', 'products', 'orders', 'staff', 'cafe_settings'];
        $tableStatus = [];

        foreach ($tables as $tableName) {
            $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $dbName = DB_NAME;
            $stmt->bind_param('ss', $dbName, $tableName);
            $stmt->execute();
            $exists = (int)$stmt->get_result()->fetch_assoc()['c'] > 0;
            $stmt->close();

            $rows = 0;
            if ($exists) {
                $rows = (int)$conn->query("SELECT COUNT(*) AS c FROM `{$tableName}`")->fetch_assoc()['c'];
            }

            $tableStatus[] = [
                'name' => $tableName,
                'exists' => $exists,
                'rows' => $rows
            ];
        }

        echo json_encode([
            'success' => true,
            'database' => DB_NAME,
            'tables' => $tableStatus,
            'message' => 'Storage initialized and verified.'
        ]);
        break;

    // ════════════════════════════════════════════════
    // PRODUCTS
    // ════════════════════════════════════════════════
    case 'get_products':
        $result = $conn->query("SELECT * FROM products ORDER BY category, name");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['price'] = (float)$row['price'];
            $row['available'] = (bool)$row['available'];
            $products[] = $row;
        }
        echo json_encode($products);
        break;

    case 'add_product':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = $data['name'] ?? '';
            $category = $data['category'] ?? '';
            $price = $data['price'] ?? 0;
            $image = $data['image'] ?? '';
            $available = !empty($data['available']) ? 1 : 0;

            if (empty($name) || empty($category)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and category required']);
                break;
            }

            $stmt = $conn->prepare("INSERT INTO products (name, category, price, image, available) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsi", $name, $category, $price, $image, $available);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add product']);
            }
            $stmt->close();
        }
        break;

    case 'update_product':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            $name = $data['name'] ?? '';
            $category = $data['category'] ?? '';
            $price = $data['price'] ?? 0;
            $image = $data['image'] ?? '';
            $available = !empty($data['available']) ? 1 : 0;

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                break;
            }

            $stmt = $conn->prepare("UPDATE products SET name=?, category=?, price=?, image=?, available=? WHERE id=?");
            $stmt->bind_param("ssdsii", $name, $category, $price, $image, $available, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update product']);
            }
            $stmt->close();
        }
        break;

    case 'delete_product':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete product']);
            }
            $stmt->close();
        }
        break;

    // ════════════════════════════════════════════════
    // ORDERS
    // ════════════════════════════════════════════════
    case 'get_orders':
        $limit = $_GET['limit'] ?? 100;
        $result = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT " . intval($limit));
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $row['items'] = json_decode($row['items'], true);
            $row['total'] = (float)$row['total'];
            $row['paid'] = (float)$row['paid'];
            $row['change_amount'] = (float)$row['change_amount'];
                // Ensure a status field exists in the returned object (DB may not have it on older installs)
                if (!array_key_exists('status', $row) || $row['status'] === null) {
                    $row['status'] = 'pending';
                }
            $orders[] = $row;
        }
        echo json_encode($orders);
        break;

        case 'update_order_status':
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $id = isset($data['id']) ? intval($data['id']) : 0;
                $status = isset($data['status']) ? $data['status'] : '';

                if (empty($id) || $status === '') {
                    http_response_code(400);
                    echo json_encode(['error' => 'Order ID and status required']);
                    break;
                }

                // Add status column if it does not exist (safe check)
                $colCheck = $conn->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'status'");
                $dbName = DB_NAME;
                $colCheck->bind_param('s', $dbName);
                $colCheck->execute();
                $exists = (int)$colCheck->get_result()->fetch_assoc()['c'] > 0;
                $colCheck->close();

                if (!$exists) {
                    // Add the column with a default value
                    $conn->query("ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'pending'");
                }

                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->bind_param('si', $status, $id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update order status']);
                }
                $stmt->close();
            }
            break;

    case 'add_order':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $order_date = $data['order_date'] ?? date('Y-m-d H:i:s');
            $items = $data['items'] ?? [];
            $total = $data['total'] ?? 0;
            $paid = $data['paid'] ?? 0;
            $change_amount = $data['change_amount'] ?? 0;

            $items_json = json_encode($items);

            $stmt = $conn->prepare("INSERT INTO orders (order_date, items, total, paid, change_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddd", $order_date, $items_json, $total, $paid, $change_amount);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add order']);
            }
            $stmt->close();
        }
        break;

    case 'clear_orders':
        if ($method === 'POST') {
            if ($conn->query("DELETE FROM orders")) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to clear orders']);
            }
        }
        break;

    // ════════════════════════════════════════════════
    // STAFF
    // ════════════════════════════════════════════════
    case 'get_staff':
        $result = $conn->query("SELECT id, name, role, username, active, created_at FROM staff ORDER BY name");
        $staff = [];
        while ($row = $result->fetch_assoc()) {
            $row['active'] = (bool)$row['active'];
            $staff[] = $row;
        }
        echo json_encode($staff);
        break;

    case 'add_staff':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $name = $data['name'] ?? '';
            $role = $data['role'] ?? '';
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $active = !empty($data['active']) ? 1 : 0;

            if (empty($name) || empty($username) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'Name, username, and password required']);
                break;
            }

            if (strlen($password) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 6 characters']);
                break;
            }

            // Check if username already exists
            $check = $conn->prepare("SELECT id FROM staff WHERE username = ? LIMIT 1");
            $check->bind_param("s", $username);
            $check->execute();
            $checkResult = $check->get_result();
            if ($checkResult->num_rows > 0) {
                $check->close();
                http_response_code(400);
                echo json_encode(['error' => 'Username already exists']);
                break;
            }
            $check->close();

            // Hash the password before storing
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO staff (name, role, username, password, active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $role, $username, $passwordHash, $active);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add staff']);
            }
            $stmt->close();
        }
        break;

    case 'update_staff':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;
            $name = $data['name'] ?? '';
            $role = $data['role'] ?? '';
            $active = !empty($data['active']) ? 1 : 0;

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Staff ID required']);
                break;
            }

            $stmt = $conn->prepare("UPDATE staff SET name=?, role=?, active=? WHERE id=?");
            $stmt->bind_param("ssii", $name, $role, $active, $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update staff']);
            }
            $stmt->close();
        }
        break;

    case 'delete_staff':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $data['id'] ?? 0;

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Staff ID required']);
                break;
            }

            $stmt = $conn->prepare("DELETE FROM staff WHERE id=?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete staff']);
            }
            $stmt->close();
        }
        break;

    // ════════════════════════════════════════════════
    // DASHBOARD STATS
    // ════════════════════════════════════════════════
    case 'get_stats':
        $products_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
        $available_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE available=1")->fetch_assoc()['count'];
        $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
        $revenue = $conn->query("SELECT SUM(total) as total FROM orders")->fetch_assoc()['total'] ?? 0;
        $staff_active = $conn->query("SELECT COUNT(*) as count FROM staff WHERE active=1")->fetch_assoc()['count'];
        
        $today = date('Y-m-d');
        $today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date)='$today'")->fetch_assoc()['count'];
        $today_revenue = $conn->query("SELECT SUM(total) as total FROM orders WHERE DATE(order_date)='$today'")->fetch_assoc()['total'] ?? 0;

        echo json_encode([
            'products_count' => (int)$products_count,
            'available_count' => (int)$available_count,
            'orders_count' => (int)$orders_count,
            'revenue' => (float)$revenue,
            'staff_active' => (int)$staff_active,
            'today_orders' => (int)$today_orders,
            'today_revenue' => (float)$today_revenue
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>
