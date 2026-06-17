<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';

if (current_user()) {
    header('Location: /inventory_pos/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([trim($_POST['email'] ?? '')]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']];
        header('Location: /inventory_pos/dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory POS Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/inventory_pos/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-screen">
<main class="login-panel">
    <h1>Inventory POS</h1>
    <p class="text-muted">Sign in to continue</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label class="form-label">Email</label>
        <input class="form-control mb-3" type="email" name="email" value="admin@example.com" required>
        <label class="form-label">Password</label>
        <input class="form-control mb-3" type="password" name="password" placeholder="admin123" required>
        <button class="btn btn-primary w-100">Login</button>
    </form>
</main>
</body>
</html>

