<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';
require_login();
verify_csrf();
$title = 'Products';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(ROLE_ADVANCED);
    if (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_POST['delete_id']]);
        flash('Product deleted.');
    } else {
        $data = [$_POST['category_id'] ?: null, trim($_POST['name']), $_POST['description'] ?? '', (float)$_POST['selling_price'], $_POST['manufacturing_date'] ?: null, $_POST['expiry_date'] ?: null, $_POST['barcode'] ?: null, $_POST['status'] ?? 'active'];
        if (!empty($_POST['id'])) {
            $data[] = (int)$_POST['id'];
            $pdo->prepare('UPDATE products SET category_id=?, name=?, description=?, selling_price=?, manufacturing_date=?, expiry_date=?, barcode=?, status=? WHERE id=?')->execute($data);
            flash('Product updated.');
        } else {
            $pdo->prepare('INSERT INTO products (category_id,name,description,selling_price,manufacturing_date,expiry_date,barcode,status) VALUES (?,?,?,?,?,?,?,?)')->execute($data);
            flash('Product created.');
        }
    }
    header('Location: index.php'); exit;
}
$categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
$edit = null;
if (isset($_GET['edit']) && can_access(ROLE_ADVANCED)) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit = $stmt->fetch();
}
$where = 'WHERE (p.name LIKE :q_name OR p.barcode LIKE :q_barcode)';
$search = '%' . trim($_GET['q'] ?? '') . '%';
$params = [':q_name' => $search, ':q_barcode' => $search];
if (!empty($_GET['category_id'])) { $where .= ' AND p.category_id=:cat'; $params[':cat'] = (int)$_GET['category_id']; }
$page = max(1, (int)($_GET['page'] ?? 1)); $limit = 10; $offset = ($page - 1) * $limit;
$stmt = $pdo->prepare("SELECT p.*, c.name category, COALESCE(SUM(s.quantity),0) stock FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN stock_entries s ON s.product_id=p.id $where GROUP BY p.id ORDER BY p.name LIMIT $limit OFFSET $offset");
$stmt->execute($params); $rows = $stmt->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<?php if (can_access(ROLE_ADVANCED)): ?>
<form class="panel mb-3" method="post">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
<div class="row g-2">
<div class="col-md-3"><input class="form-control" name="name" placeholder="Product Name" value="<?= e($edit['name'] ?? '') ?>" required></div>
<div class="col-md-2"><select class="form-select" name="category_id"><option value="">Category</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= (($edit['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-2"><input class="form-control" type="number" step="0.01" name="selling_price" placeholder="Selling Price" value="<?= e($edit['selling_price'] ?? '') ?>" required></div>
<div class="col-md-2"><input class="form-control" name="barcode" placeholder="Barcode" value="<?= e($edit['barcode'] ?? '') ?>"></div>
<div class="col-md-3"><input class="form-control" name="description" placeholder="Description" value="<?= e($edit['description'] ?? '') ?>"></div>
<div class="col-md-2"><input class="form-control" type="date" name="manufacturing_date" value="<?= e($edit['manufacturing_date'] ?? '') ?>"></div>
<div class="col-md-2"><input class="form-control" type="date" name="expiry_date" value="<?= e($edit['expiry_date'] ?? '') ?>"></div>
<div class="col-md-2"><select class="form-select" name="status"><option>active</option><option <?= (($edit['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>inactive</option></select></div>
<div class="col-md-2"><button class="btn btn-primary w-100"><?= $edit ? 'Update' : 'Add Product' ?></button></div>
</div></form><?php endif; ?>
<form class="row g-2 mb-3"><div class="col-md-8"><input class="form-control" name="q" placeholder="Search name or barcode" value="<?= e($_GET['q'] ?? '') ?>"></div><div class="col-md-3"><select class="form-select" name="category_id"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['id'] ?>" <?= (($_GET['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-1"><button class="btn btn-secondary w-100">Go</button></div></form>
<div class="table-wrap"><table class="table table-hover mb-0"><thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Mfg</th><th>Expiry</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['category']) ?></td><td><?= number_format((float)$row['selling_price'],2) ?></td><td><?= e((string)$row['stock']) ?></td><td><?= e($row['manufacturing_date']) ?></td><td><?= e($row['expiry_date']) ?></td><td><?= e($row['status']) ?></td><td class="text-end"><?php if (can_access(ROLE_ADVANCED)): ?><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $row['id'] ?>">Edit</a><form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this product?">Delete</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<div class="mt-3"><a class="btn btn-sm btn-outline-secondary" href="?page=<?= max(1,$page-1) ?>">Prev</a> <a class="btn btn-sm btn-outline-secondary" href="?page=<?= $page+1 ?>">Next</a></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>
