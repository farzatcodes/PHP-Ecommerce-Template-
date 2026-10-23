<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? $_GET['id'] : null;

if ($id) {
    $jsonFile = '../landing_pages.json';
    if (file_exists($jsonFile)) {
        $pages = json_decode(file_get_contents($jsonFile), true);
        
        foreach ($pages as $key => $page) {
            if ($page['id'] === $id) {
                // Remove the physical deployed folder
                $deployDir = '../landing/' . $page['slug'];
                if(is_dir($deployDir)) {
                    @unlink($deployDir . '/index.php'); // delete file
                    @rmdir($deployDir); // delete dir
                }
                
                // Unset from JSON
                unset($pages[$key]);
                file_put_contents($jsonFile, json_encode(array_values($pages), JSON_PRETTY_PRINT));
                break;
            }
        }
    }
}

header('Location: landing_pages.php');
exit;
?>