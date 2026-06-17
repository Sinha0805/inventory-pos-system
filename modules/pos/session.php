<?php
require __DIR__ . '/../../config/database.php'; require __DIR__ . '/../../config/auth.php';
require_role(ROLE_BASIC); verify_csrf(); $title = 'POS Session';
$userId = current_user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'open') {
        $open = $pdo->prepare("SELECT COUNT(*) FROM pos_sessions WHERE user_id=? AND status='open'"); $open->execute([$userId]);
        if (!$open->fetchColumn()) $pdo->prepare('INSERT INTO pos_sessions (user_id,opening_amount,opening_time,status) VALUES (?,?,NOW(),"open")')->execute([$userId,(float)$_POST['opening_amount']]);
        flash('Session opened.');
    } elseif ($_POST['action'] === 'close') {
        $pdo->prepare("UPDATE pos_sessions SET closing_amount=?, closing_time=NOW(), status='closed' WHERE id=? AND user_id=? AND status='open'")->execute([(float)$_POST['closing_amount'], (int)$_POST['session_id'], $userId]);
        flash('Session closed.');
    }
    header('Location: session.php'); exit;
}
$activeStmt = $pdo->prepare("SELECT * FROM pos_sessions WHERE user_id=? AND status='open' ORDER BY id DESC LIMIT 1"); $activeStmt->execute([$userId]); $active = $activeStmt->fetch();
$sessions = $pdo->prepare('SELECT s.*, COALESCE(SUM(o.grand_total),0) sales FROM pos_sessions s LEFT JOIN pos_orders o ON o.session_id=s.id WHERE s.user_id=? GROUP BY s.id ORDER BY s.id DESC'); $sessions->execute([$userId]);
require __DIR__ . '/../../templates/header.php';
?>
<div class="panel mb-3"><?php if ($active): ?><h2 class="h5">Active Session #<?= $active['id'] ?></h2><p>Opened at <?= e($active['opening_time']) ?> with <?= number_format((float)$active['opening_amount'],2) ?></p><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="close"><input type="hidden" name="session_id" value="<?= $active['id'] ?>"><div class="col-md-3"><input class="form-control" type="number" step="0.01" name="closing_amount" placeholder="Closing Amount" required></div><div class="col-md-2"><button class="btn btn-danger">Close Session</button></div></form><?php else: ?><form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="action" value="open"><div class="col-md-3"><input class="form-control" type="number" step="0.01" name="opening_amount" placeholder="Opening Amount" required></div><div class="col-md-2"><button class="btn btn-primary">Open Session</button></div></form><?php endif; ?></div>
<div class="table-wrap"><table class="table mb-0"><thead><tr><th>ID</th><th>Open</th><th>Close</th><th>Opening</th><th>Closing</th><th>Sales</th><th>Difference</th><th>Status</th></tr></thead><tbody><?php foreach ($sessions as $s): $expected=(float)$s['opening_amount']+(float)$s['sales']; ?><tr><td><?= $s['id'] ?></td><td><?= e($s['opening_time']) ?></td><td><?= e($s['closing_time']) ?></td><td><?= number_format((float)$s['opening_amount'],2) ?></td><td><?= number_format((float)$s['closing_amount'],2) ?></td><td><?= number_format((float)$s['sales'],2) ?></td><td><?= $s['closing_amount'] === null ? '-' : number_format((float)$s['closing_amount'] - $expected,2) ?></td><td><?= e($s['status']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php require __DIR__ . '/../../templates/footer.php'; ?>

