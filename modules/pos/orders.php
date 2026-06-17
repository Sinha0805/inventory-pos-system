<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_BASIC); $title = 'POS Orders';
$orders = $pdo->query('SELECT o.*, u.name user_name FROM pos_orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC LIMIT 200')->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<div class="table-wrap"><table class="table mb-0"><thead><tr><th>Order</th><th>Date</th><th>Customer</th><th>User</th><th>Total</th><th>Tax</th><th>Grand Total</th><th></th></tr></thead><tbody><?php foreach ($orders as $o): ?><tr><td><?= e($o['order_number']) ?></td><td><?= e($o['order_date']) ?></td><td><?= e($o['customer_name']) ?></td><td><?= e($o['user_name']) ?></td><td><?= number_format((float)$o['total_amount'],2) ?></td><td><?= number_format((float)$o['tax'],2) ?></td><td><?= number_format((float)$o['grand_total'],2) ?></td><td><a class="btn btn-sm btn-outline-primary" href="receipt.php?id=<?= $o['id'] ?>">Receipt</a></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

