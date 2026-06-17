<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_BASIC);
header('Content-Type: application/json');

function price_for(PDO $pdo, array $product, ?int $pricelistId): float {
    $price = (float)$product['selling_price'];
    if (!$pricelistId) return $price;
    $stmt = $pdo->prepare("SELECT * FROM price_rules WHERE pricelist_id=? AND active=1 AND (rule_type='all' OR (rule_type='category' AND category_id=?) OR (rule_type='product' AND product_id=?)) ORDER BY FIELD(rule_type,'product','category','all') LIMIT 1");
    $stmt->execute([$pricelistId, $product['category_id'], $product['id']]);
    $rule = $stmt->fetch();
    if (!$rule) return $price;
    return $rule['formula_type'] === 'percentage' ? max(0, $price + ($price * ((float)$rule['formula_value'] / 100))) : max(0, $price + (float)$rule['formula_value']);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'products') {
    $q = '%' . ($_GET['q'] ?? '') . '%'; $pl = (int)($_GET['pricelist_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT p.*, COALESCE(SUM(s.quantity),0) stock FROM products p LEFT JOIN stock_entries s ON s.product_id=p.id WHERE p.status="active" AND (p.name LIKE ? OR p.barcode LIKE ?) GROUP BY p.id ORDER BY p.name LIMIT 40');
    $stmt->execute([$q, $q]); $rows = $stmt->fetchAll();
    foreach ($rows as &$row) $row['final_price'] = price_for($pdo, $row, $pl ?: null);
    echo json_encode($rows); exit;
}
if ($action === 'checkout') {
    verify_csrf();
    $payload = json_decode($_POST['payload'] ?? '[]', true);
    $items = $payload['items'] ?? []; $payments = $payload['payments'] ?? [];
    $sessionId = (int)($payload['session_id'] ?? 0);
    $sessionStmt = $pdo->prepare("SELECT id FROM pos_sessions WHERE id=? AND user_id=? AND status='open' LIMIT 1");
    $sessionStmt->execute([$sessionId, current_user()['id']]);
    $session = $sessionStmt->fetch();
    if (!$session || !$items) { http_response_code(422); echo json_encode(['error'=>'Open a POS session and add products.']); exit; }
    $pdo->beginTransaction();
    $total = 0;
    foreach ($items as $item) {
        $qty = (int)$item['qty'];
        if ($qty <= 0) { $pdo->rollBack(); http_response_code(422); echo json_encode(['error'=>'Quantity must be greater than zero.']); exit; }
        $stockStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM stock_entries WHERE product_id=?');
        $stockStmt->execute([(int)$item['id']]);
        if ((int)$stockStmt->fetchColumn() < $qty) {
            $pdo->rollBack(); http_response_code(422); echo json_encode(['error'=>'Insufficient stock for one or more products.']); exit;
        }
        $total += ((float)$item['price'] * $qty);
    }
    $tax = round($total * 0.05, 2); $grand = $total + $tax;
    $paid = array_sum(array_map(fn($p) => (float)$p['amount'], $payments));
    if (round($paid,2) < round($grand,2)) { $pdo->rollBack(); http_response_code(422); echo json_encode(['error'=>'Payment is not complete.']); exit; }
    $orderNo = 'POS' . date('YmdHis');
    $pdo->prepare('INSERT INTO pos_orders (order_number,customer_name,session_id,user_id,order_date,total_amount,tax,grand_total) VALUES (?,?,?,?,NOW(),?,?,?)')->execute([$orderNo, $payload['customer_name'] ?? 'Walk-in', $session['id'], current_user()['id'], $total, $tax, $grand]);
    $orderId = (int)$pdo->lastInsertId();
    foreach ($items as $item) {
        $sub = (float)$item['price'] * (int)$item['qty'];
        $pdo->prepare('INSERT INTO pos_order_lines (order_id,product_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)')->execute([$orderId,(int)$item['id'],(int)$item['qty'],(float)$item['price'],$sub]);
        $pdo->prepare('INSERT INTO stock_entries (product_id,quantity,cost_price,entry_date,remarks,created_by) VALUES (? ,?,0,CURDATE(),?,?)')->execute([(int)$item['id'], -1 * (int)$item['qty'], 'POS sale ' . $orderNo, current_user()['id']]);
    }
    foreach ($payments as $payment) {
        if ((float)$payment['amount'] <= 0) continue;
        $methodStmt = $pdo->prepare('SELECT COUNT(*) FROM payment_methods WHERE id=? AND active=1');
        $methodStmt->execute([(int)$payment['method_id']]);
        if (!$methodStmt->fetchColumn()) {
            $pdo->rollBack(); http_response_code(422); echo json_encode(['error'=>'Invalid payment method.']); exit;
        }
        $pdo->prepare('INSERT INTO pos_payment_lines (order_id,payment_method_id,amount) VALUES (?,?,?)')->execute([$orderId,(int)$payment['method_id'],(float)$payment['amount']]);
    }
    $pdo->commit(); echo json_encode(['order_id'=>$orderId]); exit;
}
http_response_code(404); echo json_encode(['error'=>'Unknown action']);
