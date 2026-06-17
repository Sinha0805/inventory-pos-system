<?php
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/auth.php';
require_role(ROLE_BASIC);
$title = 'POS Screen';

$sessionId = (int)($_GET['session_id'] ?? 0);
if ($sessionId <= 0) {
    $sessionStmt = $pdo->prepare("SELECT id FROM pos_sessions WHERE user_id=? AND status='open' ORDER BY id DESC LIMIT 1");
    $sessionStmt->execute([current_user()['id']]);
    $sessionId = (int)($sessionStmt->fetchColumn() ?: 0);
}

$sessionStmt = $pdo->prepare("SELECT * FROM pos_sessions WHERE id=? AND user_id=? AND status='open'");
$sessionStmt->execute([$sessionId, current_user()['id']]);
$activeSession = $sessionStmt->fetch();
if (!$activeSession) {
    flash('Open a POS session before selling.', 'warning');
    header('Location: settings.php');
    exit;
}

$settings = $pdo->query('SELECT * FROM pos_settings ORDER BY id LIMIT 1')->fetch();
$pricelists = $pdo->query('SELECT * FROM pricelists WHERE active=1 ORDER BY name')->fetchAll();
$methods = $pdo->query('SELECT * FROM payment_methods WHERE active=1 ORDER BY name')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS Screen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/inventory_pos/assets/css/style.css" rel="stylesheet">
</head>
<body class="pos-fullscreen">
<nav class="pos-top-menu">
    <div>
        <b><?= e($settings['pos_name'] ?? 'Main POS') ?></b>
        <span>Session #<?= (int)$activeSession['id'] ?> opened at <?= e($activeSession['opening_time']) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-light btn-sm" href="settings.php">POS Settings</a>
        <a class="btn btn-outline-light btn-sm" href="orders.php">Orders</a>
        <a class="btn btn-outline-light btn-sm" href="/inventory_pos/dashboard.php">Back Office</a>
        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closePosModal">Close Session</button>
    </div>
</nav>

<main class="pos-page">
<div class="pos-layout pos-layout-full">
    <section class="panel">
        <div class="row g-2 mb-3">
            <div class="col-md-8"><input id="productSearch" class="form-control" placeholder="Search product or scan barcode"></div>
            <div class="col-md-4">
                <select id="pricelist" class="form-select">
                    <option value="">List Price</option>
                    <?php foreach ($pricelists as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (($settings['default_pricelist_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div id="productGrid" class="product-grid"></div>
    </section>

    <aside class="panel">
        <input id="customerName" class="form-control mb-2" placeholder="Customer Name">
        <div id="cart" class="cart-list mb-3"></div>
        <div class="d-flex justify-content-between"><span>Subtotal</span><b id="subtotal">0.00</b></div>
        <div class="d-flex justify-content-between"><span>Tax 5%</span><b id="tax">0.00</b></div>
        <div class="d-flex justify-content-between fs-5 border-top pt-2"><span>Grand Total</span><b id="grand">0.00</b></div>

        <h2 class="h6 mt-3">Payment</h2>
        <div class="input-group mb-2">
            <select id="paymentMethod" class="form-select">
                <?php foreach ($methods as $m): ?><option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?>
            </select>
            <input id="paymentAmount" class="form-control" type="number" step="0.01" min="0" placeholder="Amount">
            <button id="addPayment" class="btn btn-outline-primary" type="button">Add</button>
        </div>
        <div id="paymentLines" class="mb-2"></div>
        <div class="payment-summary">
            <div class="d-flex justify-content-between"><span>Paid</span><b id="paid">0.00</b></div>
            <div class="d-flex justify-content-between"><span>Remaining</span><b id="remaining">0.00</b></div>
            <div class="d-flex justify-content-between"><span>Change</span><b id="change">0.00</b></div>
        </div>
        <button id="checkout" class="btn btn-primary w-100 mt-3">Pay</button>
        <div id="posMsg" class="small mt-2"></div>
    </aside>
</div>
</main>

<div class="modal fade" id="closePosModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="post" action="settings.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="close_session">
            <input type="hidden" name="session_id" value="<?= (int)$activeSession['id'] ?>">
            <div class="modal-header"><h2 class="modal-title h5">Close POS Session</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><label class="form-label">Closing Cash</label><input class="form-control" type="number" step="0.01" min="0" name="closing_amount" required></div>
            <div class="modal-footer"><button class="btn btn-danger">Close Session</button></div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = <?= json_encode(csrf_token()) ?>;
const sessionId = <?= (int)$activeSession['id'] ?>;
let cart = [];
let payments = [];

function money(value) {
    return Number(value).toFixed(2);
}

function totals() {
    const sub = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const tax = sub * 0.05;
    const grand = sub + tax;
    const paid = payments.reduce((sum, payment) => sum + payment.amount, 0);
    return {sub, tax, grand, paid, remaining: Math.max(0, grand - paid), change: Math.max(0, paid - grand)};
}

function loadProducts() {
    $.getJSON('api.php', {action: 'products', q: $('#productSearch').val(), pricelist_id: $('#pricelist').val()}, rows => {
        $('#productGrid').html(rows.map(p => `<button class="product-tile" data-id="${p.id}" data-name="${p.name}" data-price="${p.final_price}"><b>${p.name}</b><br><span>${money(p.final_price)}</span><br><small>Stock ${p.stock}</small></button>`).join(''));
    });
}

function drawPayments() {
    $('#paymentLines').html(payments.map((payment, idx) => `<div class="d-flex align-items-center gap-2 border-bottom py-2"><div class="flex-grow-1">${payment.method}</div><b>${money(payment.amount)}</b><button class="btn btn-sm btn-outline-danger remove-payment" data-idx="${idx}">x</button></div>`).join(''));
    const t = totals();
    $('#paid').text(money(t.paid));
    $('#remaining').text(money(t.remaining));
    $('#change').text(money(t.change));
}

function drawCart() {
    $('#cart').html(cart.map((item, idx) => `<div class="d-flex align-items-center gap-2 border-bottom py-2"><div class="flex-grow-1"><b>${item.name}</b><br><small>${money(item.price)}</small></div><input class="form-control form-control-sm qty" data-idx="${idx}" type="number" min="1" value="${item.qty}" style="width:76px"><button class="btn btn-sm btn-outline-danger remove" data-idx="${idx}">x</button></div>`).join('') || '<div class="text-muted py-3">Add products to start an order.</div>');
    const t = totals();
    $('#subtotal').text(money(t.sub));
    $('#tax').text(money(t.tax));
    $('#grand').text(money(t.grand));
    drawPayments();
}

$(document).on('click', '.product-tile', function () {
    const id = $(this).data('id');
    const found = cart.find(item => item.id == id);
    if (found) found.qty++;
    else cart.push({id: id, name: $(this).data('name'), price: Number($(this).data('price')), qty: 1});
    drawCart();
});
$(document).on('input', '.qty', function () {
    cart[$(this).data('idx')].qty = Math.max(1, Number(this.value));
    drawCart();
});
$(document).on('click', '.remove', function () {
    cart.splice($(this).data('idx'), 1);
    drawCart();
});
$(document).on('click', '.remove-payment', function () {
    payments.splice($(this).data('idx'), 1);
    drawCart();
});
$('#addPayment').on('click', function () {
    const amount = Number($('#paymentAmount').val());
    const method = $('#paymentMethod option:selected');
    if (!amount || amount <= 0) {
        $('#posMsg').text('Enter a payment amount.').addClass('text-danger');
        return;
    }
    payments.push({method_id: Number(method.val()), method: method.text(), amount: amount});
    $('#paymentAmount').val('');
    $('#posMsg').text('').removeClass('text-danger');
    drawCart();
});
$('#productSearch,#pricelist').on('input change', loadProducts);
$('#checkout').on('click', function () {
    const t = totals();
    if (!cart.length) {
        $('#posMsg').text('Add at least one product.').addClass('text-danger');
        return;
    }
    if (t.paid < t.grand) {
        $('#posMsg').text('Payment is not complete. Remaining: ' + money(t.remaining)).addClass('text-danger');
        return;
    }
    $.post('api.php?action=checkout', {
        csrf_token: csrf,
        payload: JSON.stringify({session_id: sessionId, customer_name: $('#customerName').val(), items: cart, payments: payments})
    }).done(response => {
        location.href = 'receipt.php?id=' + response.order_id;
    }).fail(xhr => {
        $('#posMsg').text(xhr.responseJSON?.error || 'Checkout failed').addClass('text-danger');
    });
});

loadProducts();
drawCart();
</script>
</body>
</html>
