<?php
session_start();

require_once __DIR__ . '/cors.php';

bfcApplyCorsHeaders(true);
bfcHandleCorsPreflight();

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

include 'db-config.php';

function normalizeProductRow(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $row['price'] = (float)($row['price'] ?? 0);
    $row['available'] = (bool)($row['available'] ?? false);

    return $row;
}

function normalizeOrderRow(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $row['items'] = json_decode((string)($row['items'] ?? '[]'), true) ?: [];
    $row['total'] = (float)($row['total'] ?? 0);
    $row['paid'] = (float)($row['paid'] ?? 0);
    $row['change_amount'] = (float)($row['change_amount'] ?? 0);
    $row['status'] = (string)($row['status'] ?? 'pending');

    return $row;
}

function normalizeStaffRow(array $row): array
{
    $row['id'] = (int)($row['id'] ?? 0);
    $row['active'] = (bool)($row['active'] ?? false);

    return $row;
}

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
        $schemaName = db_is_postgres($conn) ? 'public' : DB_NAME;

        foreach ($tables as $tableName) {
            $exists = (int)(db_fetch_value(
                $conn,
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$schemaName, $tableName]
            ) ?? 0) > 0;

            $rows = 0;
            if ($exists) {
                $rows = (int)(db_fetch_value($conn, "SELECT COUNT(*) FROM {$tableName}") ?? 0);
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
        $products = array_map('normalizeProductRow', db_fetch_all($conn, 'SELECT * FROM products ORDER BY category, name'));
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

            if ($stmt->execute([$name, $category, $price, $image, $available])) {
                echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add product']);
            }
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

            if ($stmt->execute([$name, $category, $price, $image, $available, $id])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update product']);
            }
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

            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete product']);
            }
        }
        break;

    // ════════════════════════════════════════════════
    // ORDERS
    // ════════════════════════════════════════════════
    case 'get_orders':
        $limit = $_GET['limit'] ?? 100;
        $orders = array_map('normalizeOrderRow', db_fetch_all($conn, 'SELECT * FROM orders ORDER BY order_date DESC LIMIT ' . intval($limit)));
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

                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $id])) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update order status']);
                }
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

            if ($stmt->execute([$order_date, $items_json, $total, $paid, $change_amount])) {
                echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add order']);
            }
        }
        break;

    case 'clear_orders':
        if ($method === 'POST') {
            if ($conn->exec("DELETE FROM orders") !== false) {
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
        $staff = array_map('normalizeStaffRow', db_fetch_all($conn, 'SELECT id, name, role, username, active, created_at FROM staff ORDER BY name'));
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
            $existingStaffId = db_fetch_value($conn, 'SELECT id FROM staff WHERE username = ? LIMIT 1', [$username]);
            if ($existingStaffId !== null) {
                http_response_code(400);
                echo json_encode(['error' => 'Username already exists']);
                break;
            }

            // Hash the password before storing
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO staff (name, role, username, password, active) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $role, $username, $passwordHash, $active])) {
                echo json_encode(['success' => true, 'id' => (int)$conn->lastInsertId()]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add staff']);
            }
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

            if ($stmt->execute([$name, $role, $active, $id])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update staff']);
            }
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

            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to delete staff']);
            }
        }
        break;

    // ════════════════════════════════════════════════
    // DASHBOARD STATS
    // ════════════════════════════════════════════════
    case 'get_stats':
        $products_count = (int)(db_fetch_value($conn, 'SELECT COUNT(*) FROM products') ?? 0);
        $available_count = (int)(db_fetch_value($conn, 'SELECT COUNT(*) FROM products WHERE available = 1') ?? 0);
        $orders_count = (int)(db_fetch_value($conn, 'SELECT COUNT(*) FROM orders') ?? 0);
        $revenue = (float)(db_fetch_value($conn, 'SELECT COALESCE(SUM(total), 0) FROM orders') ?? 0);
        $staff_active = (int)(db_fetch_value($conn, 'SELECT COUNT(*) FROM staff WHERE active = 1') ?? 0);
        
        $today = date('Y-m-d');
        $today_orders = (int)(db_fetch_value($conn, 'SELECT COUNT(*) FROM orders WHERE DATE(order_date) = ?', [$today]) ?? 0);
        $today_revenue = (float)(db_fetch_value($conn, 'SELECT COALESCE(SUM(total), 0) FROM orders WHERE DATE(order_date) = ?', [$today]) ?? 0);

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
