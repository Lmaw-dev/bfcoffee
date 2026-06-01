<?php
require_once __DIR__ . '/cors.php';

bfcApplyCorsHeaders(true);
bfcHandleCorsPreflight();

header('Content-Type: application/json');

require_once __DIR__ . '/db-config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_products':
        $products = db_fetch_all($conn, "SELECT id, name, category, price, image, available FROM products WHERE available = 1 ORDER BY category, name");
        $normalized = [];
        foreach ($products as $row) {
            $row['id'] = (int)$row['id'];
            $row['price'] = (float)$row['price'];
            $row['available'] = (bool)$row['available'];
            $normalized[] = $row;
        }
        echo json_encode($normalized);
        break;

    case 'add_order':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request body']);
            break;
        }

        $items = $data['items'] ?? [];
        $total = isset($data['total']) ? (float)$data['total'] : 0.0;
        $paid = isset($data['paid']) ? (float)$data['paid'] : 0.0;
        $changeAmount = isset($data['change_amount']) ? (float)$data['change_amount'] : 0.0;
        $orderDate = date('Y-m-d H:i:s');

        if (!is_array($items) || count($items) === 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Order items are required']);
            break;
        }

        if ($total <= 0 || $paid < $total) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payment values']);
            break;
        }

        $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare('INSERT INTO orders (order_date, items, total, paid, change_amount) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$orderDate, $itemsJson, $total, $paid, $changeAmount]);

        echo json_encode([
            'success' => true,
            'id' => (int)$conn->lastInsertId(),
            'order_date' => $orderDate
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
?>
