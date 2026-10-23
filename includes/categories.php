<?php
/**
 * Shared category helpers.
 * Categories are stored centrally in /categories.json as [{slug, name}, ...]
 * so the admin dropdowns, storefront navigation, and category pages all
 * stay in sync instead of using separate hardcoded lists.
 */

if (!function_exists('categoriesFilePath')) {
    function categoriesFilePath() {
        return __DIR__ . '/../categories.json';
    }
}

if (!function_exists('loadCategories')) {
    function loadCategories() {
        $file = categoriesFilePath();
        if (!file_exists($file)) return [];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }
}

if (!function_exists('saveCategories')) {
    function saveCategories($categories) {
        $file = categoriesFilePath();
        $fp = fopen($file, 'c+');
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode(array_values($categories), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

if (!function_exists('categorySlug')) {
    function categorySlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) return 'category-' . time();
        return $text;
    }
}

if (!function_exists('categoryName')) {
    function categoryName($slug, $categories = null) {
        if ($categories === null) $categories = loadCategories();
        foreach ($categories as $c) {
            if ($c['slug'] === $slug) return $c['name'];
        }
        // Fallback for legacy/unknown values: prettify the raw value
        return $slug !== '' ? ucwords(str_replace('-', ' ', $slug)) : 'Uncategorized';
    }
}

if (!function_exists('countProductsInCategory')) {
    function countProductsInCategory($slug, $products) {
        $count = 0;
        foreach ($products as $p) {
            if (($p['category'] ?? '') === $slug) $count++;
        }
        return $count;
    }
}
