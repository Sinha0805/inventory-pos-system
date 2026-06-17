<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_ADVANCED); verify_csrf(); $title = 'Payment Methods';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) $pdo->prepare('DELETE FROM payment_methods WHERE id=?')->execute([(int)$_POST['delete_id']]);
    elseif (!empty($_POST['id'])) $pdo->prepare('UPDATE payment_methods SET name=?, active=? WHERE id=?')->execute([trim($_POST['name']), isset($_POST['active']) ? 1 : 0, (int)$_POST['id']]);
    else $pdo->prepare('INSERT INTO payment_methods (name,active) VALUES (?,?)')->execute([trim($_POST['name']), isset($_POST['active']) ? 1 : 0]);
    flash('Payment method saved.'); header('Location: payment_methods.php'); exit;
}
$edit = null; if (isset($_GET['edit'])) { $stmt=$pdo->prepare('SELECT * FROM payment_methods WHERE id=?'); $stmt->execute([(int)$_GET['edit']]); $edit=$stmt->fetch(); }
$methods = $pdo->query('SELECT * FROM payment_methods ORDER BY name')->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<form class="panel mb-3" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>"><div class="row g-2"><div class="col-md-6"><input class="form-control" name="name" placeholder="Payment method" value="<?= e($edit['name'] ?? '') ?>" required></div><div class="col-md-2"><label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="active" <?= !isset($edit['active']) || $edit['active'] ? 'checked' : '' ?>> Active</label></div><div class="col-md-2"><button class="btn btn-primary w-100"><?= $edit ? 'Update' : 'Add' ?></button></div></div></form>
<div class="table-wrap"><table class="table mb-0"><thead><tr><th>Name</th><th>Active</th><th></th></tr></thead><tbody><?php foreach ($methods as $m): ?><tr><td><?= e($m['name']) ?></td><td><?= $m['active'] ? 'Yes' : 'No' ?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $m['id'] ?>">Edit</a><form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $m['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete method?">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

