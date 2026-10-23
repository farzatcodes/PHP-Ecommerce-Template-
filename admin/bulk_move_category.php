<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/categories.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

csrf_verify();

$ids = $_POST['product_ids'] ?? [];
$targetSlug = $_POST['target_category'] ?? '';

if (!empty($ids) && $targetSlug !== '') {
    // Validate the target category actually exists
    $categories = loadCategories();
    $validSlug = false;
    foreach ($categories as $c) {
        if ($c['slug'] === $targetSlug) { $validSlug = true; break; }
    }

    if ($validSlug) {
        $jsonFile = '../products.json';
        $fp = fopen($jsonFile, 'c+');
        flock($fp, LOCK_EX);
        $products = json_decode(stream_get_contents($fp), true) ?: [];

        $moved = 0;
        foreach ($products as &$p) {
            if (in_array($p['id'], $ids, true)) {
                $p['category'] = $targetSlug;
                $moved++;
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
    }
}

header('Location: products.php?moved=1');
exit;
