<?php
/**
 * Responsive product-image helpers.
 *
 * Product photos are stored at their original upload resolution (often
 * 500-1500px) but are only ever displayed at ~70-340px on the storefront.
 * To avoid shipping full-size images into small grid tiles, a build step
 * (see /docs or ask Claude to re-run it after adding new products) writes
 * pre-resized WebP variants next to each original:
 *
 *   main_123.webp            <- original, used for zoom/detail view
 *   main_123_thumb.webp      <- ~110px cap, for the 70x70 gallery strip
 *   main_123_grid.webp       <- ~340px cap, for product grid/card tiles
 *   main_123_detail.webp     <- ~900px cap, for the main product viewer
 *
 * productImageVariant() returns the variant path if it exists on disk,
 * and silently falls back to the original URL otherwise so nothing ever
 * breaks for an image that hasn't been processed yet (e.g. a brand new
 * upload before the next optimization pass runs).
 */

if (!function_exists('productImageVariant')) {
    function productImageVariant(?string $url, string $variant = 'grid'): string {
        if (empty($url)) {
            return (string) $url;
        }
        if (!preg_match('#^(/assets/products/.+)\.webp$#', $url, $m)) {
            // Not a local product image (e.g. a placeholder/external URL) — leave as-is.
            return $url;
        }
        $variantUrl = $m[1] . '_' . $variant . '.webp';

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot === '') {
            // Fallback for CLI/unusual SAPIs: assume this file lives in <root>/includes/.
            $docRoot = dirname(__DIR__);
        }
        $fsPath = rtrim($docRoot, '/') . $variantUrl;

        return is_file($fsPath) ? $variantUrl : $url;
    }
}
