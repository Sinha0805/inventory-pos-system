<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/config/auth.php';
require_login();
$title = 'Dashboard';

$metrics = [
    'Total Products' => (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'Total Categories' => (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
    'Current Stock' => (int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM stock_entries')->fetchColumn(),
    "Today's Sales" => (float)$pdo->query("SELECT COALESCE(SUM(grand_total),0) FROM pos_orders WHERE DATE(order_date)=CURDATE()")->fetchColumn(),
    'Open POS Sessions' => (int)$pdo->query("SELECT COUNT(*) FROM pos_sessions WHERE status='open'")->fetchColumn(),
];
$daily = $pdo->query("SELECT DATE(order_date) day, SUM(grand_total) total FROM pos_orders GROUP BY DATE(order_date) ORDER BY day DESC LIMIT 7")->fetchAll();
$top = $pdo->query("SELECT p.name, SUM(l.quantity) qty FROM pos_order_lines l JOIN products p ON p.id=l.product_id GROUP BY p.id ORDER BY qty DESC LIMIT 5")->fetchAll();
require __DIR__ . '/templates/header.php';
?>
<div class="row g-3 mb-3">
    <?php foreach ($metrics as $label => $value): ?>
        <div class="col-md"><div class="metric"><span><?= e($label) ?></span><b><?= is_float($value) ? number_format($value, 2) : e((string)$value) ?></b></div></div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-7"><div class="panel"><canvas id="dailySales"></canvas></div></div>
    <div class="col-lg-5"><div class="panel"><canvas id="topProducts"></canvas></div></div>
</div>
<script>
window.addEventListener('load', function () {
    new Chart(document.getElementById('dailySales'), {type:'line', data:{labels:<?= json_encode(array_reverse(array_column($daily,'day'))) ?>, datasets:[{label:'Daily Sales', data:<?= json_encode(array_reverse(array_column($daily,'total'))) ?>, borderColor:'#2563eb', tension:.25}]}});
    new Chart(document.getElementById('topProducts'), {type:'bar', data:{labels:<?= json_encode(array_column($top,'name')) ?>, datasets:[{label:'Units Sold', data:<?= json_encode(array_column($top,'qty')) ?>, backgroundColor:'#10b981'}]}});
});
</script>
<?php require __DIR__ . '/templates/footer.php'; ?>

