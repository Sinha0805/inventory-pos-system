<aside class="sidebar">
    <a class="brand" href="/inventory_pos/dashboard.php">Inventory POS</a>
    <a href="/inventory_pos/dashboard.php">Dashboard</a>
    <a href="/inventory_pos/modules/categories/index.php">Categories</a>
    <a href="/inventory_pos/modules/products/index.php">Products</a>
    <a href="/inventory_pos/modules/stock/index.php">Stock</a>
    <?php if (can_access(ROLE_ADVANCED)): ?>
        <a href="/inventory_pos/modules/pricelist/index.php">Pricelists</a>
        <a href="/inventory_pos/modules/users/index.php">Users</a>
        <a href="/inventory_pos/modules/pos/payment_methods.php">Payment Methods</a>
    <?php endif; ?>
    <?php if (can_access(ROLE_BASIC)): ?>
        <a href="/inventory_pos/modules/pos/settings.php">POS Settings</a>
        <a href="/inventory_pos/modules/pos/session.php">POS Session</a>
        <a href="/inventory_pos/modules/pos/orders.php">Orders</a>
    <?php endif; ?>
    <a href="/inventory_pos/modules/reports/index.php">Reports</a>
    <a href="/inventory_pos/version.php">Version Info</a>
</aside>
