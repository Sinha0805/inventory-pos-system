<?php
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/version.php';
require_login();
$title = 'Version Info';
require __DIR__ . '/templates/header.php';
?>
<div class="panel">
    <h2 class="h5"><?= e(APP_NAME) ?></h2>
    <table class="table mb-0">
        <tbody>
            <tr><th>Version</th><td><?= e(APP_VERSION) ?></td></tr>
            <tr><th>Release Date</th><td><?= e(APP_RELEASE_DATE) ?></td></tr>
            <tr><th>Build</th><td><?= e(APP_BUILD) ?></td></tr>
            <tr><th>Stack</th><td>PHP 8+, MySQL, Bootstrap 5, jQuery, AJAX</td></tr>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/templates/footer.php'; ?>
