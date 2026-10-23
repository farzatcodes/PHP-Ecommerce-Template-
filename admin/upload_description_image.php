<?php
/**
 * AJAX endpoint used by the product description rich-text editor
 * (upload button + paste-to-upload, similar to Alibaba's product editor).
 * Accepts a single image file, stores it, and returns its public URL as JSON.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authorized.']);
    exit;
}

require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// CSRF check (token sent as a form field alongside the file)
$submitted = $_POST['csrf_token'] ?? '';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token mismatch.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No valid image uploaded.']);
    exit;
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported image type.']);
    exit;
}

// Basic sanity check that this is actually an image
$imageInfo = @getimagesize($_FILES['image']['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File is not a valid image.']);
    exit;
}

$uploadDir = '../assets/products/_description-images/';
$publicPrefix = '/assets/products/_description-images/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$newFileName = 'desc_' . date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newFileName)) {
    echo json_encode(['success' => true, 'url' => $publicPrefix . $newFileName]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save the image.']);
}
