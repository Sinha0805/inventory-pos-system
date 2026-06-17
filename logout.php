<?php
require __DIR__ . '/config/auth.php';
$_SESSION = [];
session_destroy();
header('Location: /inventory_pos/login.php');
exit;

