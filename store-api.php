<?php
// Allow CORS for local development and accept JSON requests
$allowed = [
    'http://localhost:3001',
    'http://localhost:3000'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
} else {
    // fallback to localhost (no credentials)
    header('Access-Control-Allow-Origin: http://localhost:3001');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // respond to preflight
    if (in_array($origin, $allowed, true)) {
        header('Access-Control-Allow-Credentials: true');
    }
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

require_once __DIR__ . '/db-config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_products':
        $result = $conn->query("SELECT id, name, category, price, image, available FROM products WHERE available = 1 ORDER BY category, name");
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $row['id'] = (int)$row['id'];
            $row['price'] = (float)$row['price'];
            $row['available'] = (bool)$row['available'];
            $products[] = $row;
        }
        echo json_encode($products);
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
        $stmt->bind_param('ssddd', $orderDate, $itemsJson, $total, $paid, $changeAmount);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'id' => (int)$conn->insert_id,
            'order_date' => $orderDate
        ]);

        $stmt->close();
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

$conn->close();
?>
