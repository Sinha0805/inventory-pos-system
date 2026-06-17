<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';
require_login();
verify_csrf();
$title = 'Categories';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(ROLE_ADVANCED);
    if (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_POST['delete_id']]);
        flash('Category deleted.');
    } else {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') { flash('Category name is required.', 'danger'); }
        elseif (!empty($_POST['id'])) {
            $pdo->prepare('UPDATE categories SET name=?, description=? WHERE id=?')->execute([$name, $_POST['description'] ?? '', (int)$_POST['id']]);
            flash('Category updated.');
        } else {
            $pdo->prepare('INSERT INTO categories (name, description) VALUES (?,?)')->execute([$name, $_POST['description'] ?? '']);
            flash('Category created.');
        }
    }
    header('Location: index.php'); exit;
}

$edit = null;
if (isset($_GET['edit']) && can_access(ROLE_ADVANCED)) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$q = '%' . trim($_GET['q'] ?? '') . '%';
$stmt = $pdo->prepare('SELECT * FROM categories WHERE name LIKE ? OR description LIKE ? ORDER BY name');
$stmt->execute([$q, $q]);
$rows = $stmt->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<?php if (can_access(ROLE_ADVANCED)): ?>
<form class="panel mb-3" method="post">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">
    <div class="row g-2">
        <div class="col-md-4"><input class="form-control" name="name" placeholder="Category Name" value="<?= e($edit['name'] ?? '') ?>" required></div>
        <div class="col-md-6"><input class="form-control" name="description" placeholder="Description" value="<?= e($edit['description'] ?? '') ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><?= $edit ? 'Update' : 'Add' ?></button></div>
    </div>
</form>
<?php endif; ?>
<form class="mb-3"><input class="form-control" name="q" placeholder="Search categories" value="<?= e($_GET['q'] ?? '') ?>"></form>
<div class="table-wrap"><table class="table table-hover mb-0"><thead><tr><th>Name</th><th>Description</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['description']) ?></td><td class="text-end">
<?php if (can_access(ROLE_ADVANCED)): ?><a class="btn btn-sm btn-outline-primary" href="?edit=<?= $row['id'] ?>">Edit</a>
<form class="d-inline" method="post"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete this category?">Delete</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

