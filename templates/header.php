<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/version.php';
require_login();
$flash = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Inventory POS') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/inventory_pos/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/sidebar.php'; ?>
<main class="content">
    <nav class="topbar">
        <div>
            <h1><?= e($title ?? 'Dashboard') ?></h1>
            <span><?= e(current_user()['name'] ?? '') ?> / <?= e(current_user()['role'] ?? '') ?> / v<?= e(APP_VERSION) ?></span>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="/inventory_pos/logout.php">Logout</a>
    </nav>
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>

