<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_ADVANCED); verify_csrf(); $title = 'User Management';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $pdo->prepare('DELETE FROM users WHERE id=? AND id<>?')->execute([(int)$_POST['delete_id'], current_user()['id']]);
        flash('User deleted.');
    } else {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)')->execute([trim($_POST['name']), trim($_POST['email']), $password, $_POST['role']]);
        flash('User registered.');
    }
    header('Location: index.php'); exit;
}
$users = $pdo->query('SELECT id,name,email,role,created_at FROM users ORDER BY id DESC')->fetchAll();
require __DIR__ . '/../../templates/header.php';
?>
<form class="panel mb-3" method="post"><?= csrf_field() ?><div class="row g-2"><div class="col-md-3"><input class="form-control" name="name" placeholder="Name" required></div><div class="col-md-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div><div class="col-md-2"><input class="form-control" type="password" name="password" placeholder="Password" required></div><div class="col-md-2"><select class="form-select" name="role"><option value="minimal">Minimal</option><option value="basic">Basic</option><option value="advanced">Advanced</option></select></div><div class="col-md-2"><button class="btn btn-primary w-100">Register</button></div></div></form>
<div class="table-wrap"><table class="table mb-0"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th></th></tr></thead><tbody><?php foreach ($users as $u): ?><tr><td><?= e($u['name']) ?></td><td><?= e($u['email']) ?></td><td><?= e($u['role']) ?></td><td><?= e($u['created_at']) ?></td><td class="text-end"><form method="post"><?= csrf_field() ?><input type="hidden" name="delete_id" value="<?= $u['id'] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Delete user?">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

