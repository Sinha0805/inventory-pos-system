<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_ADVANCED); verify_csrf(); $title = 'Pricelists & Rules';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] === 'pricelist') {
        $pdo->prepare('INSERT INTO pricelists (name,active) VALUES (?,?)')->execute([trim($_POST['name']), isset($_POST['active']) ? 1 : 0]);
        flash('Pricelist added.');
    } elseif ($_POST['form'] === 'rule') {
        $pdo->prepare('INSERT INTO price_rules (pricelist_id,name,rule_type,category_id,product_id,base_type,base_pricelist_id,formula_type,formula_value,active) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([(int)$_POST['pricelist_id'], trim($_POST['name']), $_POST['rule_type'], $_POST['category_id'] ?: null, $_POST['product_id'] ?: null, $_POST['base_type'], $_POST['base_pricelist_id'] ?: null, $_POST['formula_type'], (float)$_POST['formula_value'], isset($_POST['active']) ? 1 : 0]);
        flash('Price rule added.');
    }
    header('Location: index.php'); exit;
}
$pricelists = $pdo->query('SELECT * FROM pricelists ORDER BY name')->fetchAll();
$categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$products = $pdo->query('SELECT id,name FROM products ORDER BY name')->fetchAll();
$rules = $pdo->query('SELECT r.*, pl.name pricelist FROM price_rules r JOIN pricelists pl ON pl.id=r.pricelist_id ORDER BY r.id DESC')->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<div class="row g-3 mb-3"><div class="col-lg-4"><form class="panel" method="post"><?= csrf_field() ?><input type="hidden" name="form" value="pricelist"><h2 class="h5">Add Pricelist</h2><input class="form-control mb-2" name="name" placeholder="Retail Price" required><label class="form-check"><input class="form-check-input" type="checkbox" name="active" checked> Active</label><button class="btn btn-primary mt-2">Save</button></form></div>
<div class="col-lg-8"><form class="panel" method="post"><?= csrf_field() ?><input type="hidden" name="form" value="rule"><h2 class="h5">Add Price Rule</h2><div class="row g-2"><div class="col-md-4"><input class="form-control" name="name" placeholder="Rule Name" required></div><div class="col-md-4"><select class="form-select" name="pricelist_id"><?php foreach ($pricelists as $pl): ?><option value="<?= $pl['id'] ?>"><?= e($pl['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><select class="form-select" name="rule_type"><option value="all">All Products</option><option value="category">Product Category</option><option value="product">Individual Product</option></select></div><div class="col-md-4"><select class="form-select" name="category_id"><option value="">Category if needed</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><select class="form-select" name="product_id"><option value="">Product if needed</option><?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><select class="form-select" name="base_type"><option value="list_price">Product List Price</option><option value="pricelist">Another Pricelist</option></select></div><div class="col-md-4"><select class="form-select" name="base_pricelist_id"><option value="">Base Pricelist</option><?php foreach ($pricelists as $pl): ?><option value="<?= $pl['id'] ?>"><?= e($pl['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><select class="form-select" name="formula_type"><option value="percentage">Percentage</option><option value="fixed">Fixed</option></select></div><div class="col-md-2"><input class="form-control" type="number" step="0.01" name="formula_value" placeholder="+10 or -5" required></div><div class="col-md-2"><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="active" checked> Active</label></div><div class="col-md-12"><button class="btn btn-primary">Save Rule</button></div></div></form></div></div>
<div class="table-wrap"><table class="table mb-0"><thead><tr><th>Pricelist</th><th>Rule</th><th>Type</th><th>Formula</th><th>Active</th></tr></thead><tbody><?php foreach ($rules as $r): ?><tr><td><?= e($r['pricelist']) ?></td><td><?= e($r['name']) ?></td><td><?= e($r['rule_type']) ?></td><td><?= e($r['formula_type']) ?> <?= e((string)$r['formula_value']) ?></td><td><?= $r['active'] ? 'Yes' : 'No' ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

