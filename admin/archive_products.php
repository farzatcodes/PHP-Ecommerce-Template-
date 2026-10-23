<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}
csrf_verify();

/**
 * Archiving never deletes anything: it sets "archived": true on the product in
 * products.json. Archived products are hidden from the storefront (home,
 * category, search, product pages) and listed under Products > Archived,
 * where they can be restored at any time.
 *
 * action = archive_all | restore_all | archive | restore (the last two need id)
 */
$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? '';
$jsonFile = __DIR__ . '/../products.json';

if (!in_array($action, ['archive_all', 'restore_all', 'archive', 'restore'], true) || !file_exists($jsonFile)) {
    header('Location: products.php?archive_error=1');
    exit;
}

$fp = fopen($jsonFile, 'c+');
flock($fp, LOCK_EX);
$products = json_decode(stream_get_contents($fp), true);
if (!is_array($products)) {
    flock($fp, LOCK_UN);
    fclose($fp);
    header('Location: products.php?archive_error=1');
    exit;
}

$changed = 0;
foreach ($products as &$p) {
    $isArchived = !empty($p['archived']);
    switch ($action) {
        case 'archive_all':
            if (!$isArchived) { $p['archived'] = true; $changed++; }
            break;
        case 'restore_all':
            if ($isArchived) { unset($p['archived']); $changed++; }
            break;
        case 'archive':
            if ((string)$p['id'] === (string)$id && !$isArchived) { $p['archived'] = true; $changed++; }
            break;
        case 'restore':
            if ((string)$p['id'] === (string)$id && $isArchived) { unset($p['archived']); $changed++; }
            break;
    }
}
unset($p);

$json = json_encode($products, JSON_PRETTY_PRINT);
ftruncate($fp, 0);
rewind($fp);
fwrite($fp, $json);
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

$restoring = in_array($action, ['restore_all', 'restore'], true);
header('Location: products.php?' . ($restoring ? 'restored=' : 'archived=') . $changed
    . ($restoring && $action === 'restore' ? '&view=archived' : '') );
exit;
