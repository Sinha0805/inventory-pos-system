<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';
require_role(ROLE_BASIC);
verify_csrf();
$title = 'POS Configuration';
$userId = current_user()['id'];

function active_pos_session(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM pos_sessions WHERE user_id=? AND status='open' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'save_settings') {
        require_role(ROLE_ADVANCED);
        $pdo->prepare('UPDATE pos_settings SET pos_name=?, warehouse_name=?, active=?, default_pricelist_id=?, receipt_footer_message=? WHERE id=?')
            ->execute([$_POST['pos_name'], $_POST['warehouse_name'], isset($_POST['active']) ? 1 : 0, $_POST['default_pricelist_id'] ?: null, $_POST['receipt_footer_message'], (int)$_POST['id']]);
        flash('POS settings saved.');
        header('Location: settings.php');
        exit;
    }

    if ($action === 'open_session') {
        $active = active_pos_session($pdo, $userId);
        if (!$active) {
            $pdo->prepare('INSERT INTO pos_sessions (user_id,opening_amount,opening_time,status) VALUES (?,?,NOW(),"open")')
                ->execute([$userId, (float)($_POST['opening_amount'] ?? 0)]);
            $sessionId = (int)$pdo->lastInsertId();
        } else {
            $sessionId = (int)$active['id'];
        }
        header('Location: index.php?session_id=' . $sessionId);
        exit;
    }

    if ($action === 'continue_session') {
        $active = active_pos_session($pdo, $userId);
        if (!$active) {
            flash('Open a POS session before selling.', 'warning');
            header('Location: settings.php');
            exit;
        }
        header('Location: index.php?session_id=' . (int)$active['id']);
        exit;
    }

    if ($action === 'close_session') {
        $pdo->prepare("UPDATE pos_sessions SET closing_amount=?, closing_time=NOW(), status='closed' WHERE id=? AND user_id=? AND status='open'")
            ->execute([(float)($_POST['closing_amount'] ?? 0), (int)$_POST['session_id'], $userId]);
        flash('POS session closed.');
        header('Location: settings.php');
        exit;
    }
}

$settings = $pdo->query('SELECT * FROM pos_settings ORDER BY id LIMIT 1')->fetch();
$pricelists = $pdo->query('SELECT * FROM pricelists WHERE active=1 ORDER BY name')->fetchAll();
$active = active_pos_session($pdo, $userId);
$sessionSales = 0.0;
if ($active) {
    $salesStmt = $pdo->prepare('SELECT COALESCE(SUM(grand_total),0) FROM pos_orders WHERE session_id=?');
    $salesStmt->execute([(int)$active['id']]);
    $sessionSales = (float)$salesStmt->fetchColumn();
}
require __DIR__ . '/../../templates/header.php';
?>
<div class="pos-kanban mb-3">
    <section class="pos-card">
        <div>
            <h2><?= e($settings['pos_name'] ?? 'Main POS') ?></h2>
            <p><?= e($settings['warehouse_name'] ?? 'Main Warehouse') ?></p>
        </div>
        <?php if ($active): ?>
            <span class="badge text-bg-success">Session Open</span>
            <dl class="session-stats">
                <div><dt>Opening Cash</dt><dd><?= number_format((float)$active['opening_amount'], 2) ?></dd></div>
                <div><dt>Sales</dt><dd><?= number_format($sessionSales, 2) ?></dd></div>
                <div><dt>Expected Cash</dt><dd><?= number_format((float)$active['opening_amount'] + $sessionSales, 2) ?></dd></div>
            </dl>
            <div class="d-flex gap-2 flex-wrap">
                <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="continue_session"><button class="btn btn-primary">Continue Selling</button></form>
                <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#closeSessionModal">Close Session</button>
            </div>
        <?php else: ?>
            <span class="badge text-bg-secondary">No Open Session</span>
            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#openSessionModal">Open Session</button>
        <?php endif; ?>
    </section>
</div>

<?php if (can_access(ROLE_ADVANCED)): ?>
<form class="panel" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save_settings">
    <input type="hidden" name="id" value="<?= e($settings['id'] ?? '1') ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">POS Name</label><input class="form-control" name="pos_name" value="<?= e($settings['pos_name'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Warehouse Name</label><input class="form-control" name="warehouse_name" value="<?= e($settings['warehouse_name'] ?? '') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Default Pricelist</label><select class="form-select" name="default_pricelist_id"><?php foreach ($pricelists as $p): ?><option value="<?= $p['id'] ?>" <?= (($settings['default_pricelist_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-8"><label class="form-label">Receipt Footer Message</label><input class="form-control" name="receipt_footer_message" value="<?= e($settings['receipt_footer_message'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-check mt-4"><input class="form-check-input" type="checkbox" name="active" <?= !empty($settings['active']) ? 'checked' : '' ?>> Active</label></div>
        <div class="col-md-2"><button class="btn btn-primary mt-4 w-100">Save</button></div>
    </div>
</form>
<?php endif; ?>

<div class="modal fade" id="openSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="open_session"><div class="modal-header"><h2 class="modal-title h5">Open Session</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Opening Cash</label><input class="form-control" type="number" step="0.01" min="0" name="opening_amount" value="0" required></div><div class="modal-footer"><button class="btn btn-primary">Open Session</button></div></form></div>
</div>

<?php if ($active): ?>
<div class="modal fade" id="closeSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="close_session"><input type="hidden" name="session_id" value="<?= (int)$active['id'] ?>"><div class="modal-header"><h2 class="modal-title h5">Close Session</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="text-muted">Expected cash: <?= number_format((float)$active['opening_amount'] + $sessionSales, 2) ?></p><label class="form-label">Closing Cash</label><input class="form-control" type="number" step="0.01" min="0" name="closing_amount" value="<?= number_format((float)$active['opening_amount'] + $sessionSales, 2, '.', '') ?>" required></div><div class="modal-footer"><button class="btn btn-danger">Close Session</button></div></form></div>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../../templates/footer.php'; ?>
