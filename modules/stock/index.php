<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';
require_login(); verify_csrf(); $title = 'Stock Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(ROLE_BASIC);
    $pdo->prepare('INSERT INTO stock_entries (product_id,quantity,cost_price,entry_date,remarks,created_by) VALUES (?,?,?,?,?,?)')
        ->execute([(int)$_POST['product_id'], (int)$_POST['quantity'], (float)$_POST['cost_price'], $_POST['entry_date'], $_POST['remarks'] ?? '', current_user()['id']]);
    flash('Stock entry added.'); header('Location: index.php'); exit;
}
$products = $pdo->query('SELECT id,name FROM products WHERE status="active" ORDER BY name')->fetchAll();
$history = $pdo->query('SELECT s.*, p.name product, u.name user_name FROM stock_entries s JOIN products p ON p.id=s.product_id LEFT JOIN users u ON u.id=s.created_by ORDER BY s.entry_date DESC, s.id DESC LIMIT 100')->fetchAll();
$summary = $pdo->query('SELECT p.name, COALESCE(SUM(s.quantity),0) stock FROM products p LEFT JOIN stock_entries s ON s.product_id=p.id GROUP BY p.id ORDER BY p.name')->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<?php if (can_access(ROLE_BASIC)): ?><form class="panel mb-3" method="post"><?= csrf_field() ?><div class="row g-2"><div class="col-md-3"><select class="form-select" name="product_id" required><option value="">Product</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><input class="form-control" type="number" name="quantity" placeholder="Quantity" required></div><div class="col-md-2"><input class="form-control" type="number" step="0.01" name="cost_price" placeholder="Cost Price" required></div><div class="col-md-2"><input class="form-control" type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required></div><div class="col-md-2"><input class="form-control" name="remarks" placeholder="Remarks"></div><div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div></div></form><?php endif; ?>
<div class="row g-3"><div class="col-lg-5"><div class="panel"><h2 class="h5">Product Stock Summary</h2><table class="table"><tbody><?php foreach ($summary as $s): ?><tr><td><?= e($s['name']) ?></td><td class="text-end"><?= e((string)$s['stock']) ?></td></tr><?php endforeach; ?></tbody></table></div></div><div class="col-lg-7"><div class="table-wrap"><table class="table mb-0"><thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Cost</th><th>User</th><th>Remarks</th></tr></thead><tbody><?php foreach ($history as $h): ?><tr><td><?= e($h['entry_date']) ?></td><td><?= e($h['product']) ?></td><td><?= e((string)$h['quantity']) ?></td><td><?= number_format((float)$h['cost_price'],2) ?></td><td><?= e($h['user_name']) ?></td><td><?= e($h['remarks']) ?></td></tr><?php endforeach; ?></tbody></table></div></div></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

