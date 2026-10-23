<?php
/**
 * Returns a JSON list of previously-uploaded product/landing images so the
 * landing page editor can offer a "choose from gallery" picker instead of
 * forcing a fresh upload every time.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authorized.']);
    exit;
}

$roots = [
    ['dir' => __DIR__ . '/../assets/products', 'prefix' => '/assets/products', 'label' => 'Products'],
    ['dir' => __DIR__ . '/../assets/landing',  'prefix' => '/assets/landing',  'label' => 'Landing Pages'],
];

$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$images = [];

foreach ($roots as $root) {
    if (!is_dir($root['dir'])) continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root['dir'], FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;

        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root['dir'])));
        $url = $root['prefix'] . $relative;

        $images[] = [
            'url'     => $url,
            'name'    => $file->getFilename(),
            'source'  => $root['label'],
            'mtime'   => $file->getMTime(),
        ];
    }
}

// Newest first
usort($images, function ($a, $b) { return $b['mtime'] <=> $a['mtime']; });

// Cap to a reasonable number so the picker stays fast
$images = array_slice($images, 0, 300);

echo json_encode(['success' => true, 'images' => $images]);
