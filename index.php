<?php
require __DIR__ . '/config/auth.php';
header('Location: ' . (current_user() ? '/inventory_pos/dashboard.php' : '/inventory_pos/login.php'));
exit;

