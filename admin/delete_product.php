<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$idToDelete = $_GET['id'] ?? null;

if ($idToDelete) {
    $jsonFile = '../products.json';
    
    if (file_exists($jsonFile)) {
        $products = json_decode(file_get_contents($jsonFile), true);
        
        if (is_array($products)) {
            // Filter out the product with the matching ID
            $updatedProducts = array_filter($products, function($product) use ($idToDelete) {
                return $product['id'] != $idToDelete;
            });
            
            // Re-index array (array_values) so JSON encodes as an array, not an object
            $updatedProducts = array_values($updatedProducts);
            
            if (file_put_contents($jsonFile, json_encode($updatedProducts, JSON_PRETTY_PRINT))) {
                header("Location: index.php?msg=Product deleted successfully");
                exit;
            }
        }
    }
}

// Fallback if something fails
header("Location: index.php?msg=Failed to delete product");
exit;
?>