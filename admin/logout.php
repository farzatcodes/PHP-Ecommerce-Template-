<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Clear remember-me token from DB
if (!empty($_COOKIE['admin_remember'])) {
    $parts = explode(':', $_COOKIE['admin_remember'], 2);
    if (count($parts) === 2) {
        require_once __DIR__ . '/db.php';
        $pdo->prepare("UPDATE `admins` SET `remember_token`=NULL, `remember_expires`=NULL WHERE `id`=?")
            ->execute([(int)$parts[0]]);
    }
    setcookie('admin_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']);
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
