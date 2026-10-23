<?php
/**
 * ONE-TIME permission-repair script for thirstybd.com
 *
 * What happened: the deployment zip preserved read-only (444) permissions on
 * every file, so the site can't write orders.json, settings.json, the admin
 * JSON files, or the Pathao token cache. This script walks the site
 * folder it's placed in and sets:
 *   - directories -> 0755 (rwxr-xr-x)
 *   - files       -> 0644 (rw-r--r--)
 * which is enough for PHP (running as your hosting account's own user) to
 * read everything and write to the JSON/cache files, while still keeping
 * the site's own .htaccess rules in charge of what's reachable over HTTP.
 *
 * HOW TO USE
 *   1. Upload this file into your site's root folder (same folder as
 *      orders.json / settings.json / .htaccess) via cPanel File Manager.
 *   2. Visit it once in your browser:
 *      https://thirstybd.com/fix_permissions.php?run=yes
 *   3. Read the report it prints.
 *   4. DELETE THIS FILE immediately after running it. It changes
 *      permissions across your whole site and has no login of its own, so
 *      it should not be left sitting on a live server.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (($_GET['run'] ?? '') !== 'yes') {
    header('Content-Type: text/plain');
    echo "This script has not run yet.\n";
    echo "Open it with ?run=yes added to the URL to actually apply the fix, e.g.:\n";
    echo "  " . (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . $_SERVER['SCRIPT_NAME'] . "?run=yes\n";
    exit;
}

// Directory this script lives in — treated as the site root.
$root = __DIR__;

// Folders we never need to touch even if they happen to exist here.
$skipDirNames = ['node_modules', '.git'];

// This script's own filename, so it doesn't need to chmod itself.
$selfName = basename(__FILE__);

$dirCount = 0;
$fileCount = 0;
$failures = [];

$dirIterator = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
$filter = new RecursiveCallbackFilterIterator($dirIterator, function ($current) use ($skipDirNames) {
    if ($current->isDir() && in_array($current->getFilename(), $skipDirNames, true)) {
        return false; // don't descend into these
    }
    return true;
});
$iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);

foreach ($iterator as $item) {
    $path = $item->getPathname();

    if ($item->isDir()) {
        if (@chmod($path, 0755)) {
            $dirCount++;
        } else {
            $failures[] = "$path (dir -> 0755)";
        }
        continue;
    }

    if ($item->getFilename() === $selfName) {
        continue; // skip this script itself
    }

    if (@chmod($path, 0644)) {
        $fileCount++;
    } else {
        $failures[] = "$path (file -> 0644)";
    }
}

// Also make sure the root folder itself is 0755.
@chmod($root, 0755);

header('Content-Type: text/plain');
echo "Permission fix complete.\n\n";
echo "Directories set to 0755: $dirCount\n";
echo "Files set to 0644:       $fileCount\n\n";

if (empty($failures)) {
    echo "No failures — every item was updated successfully.\n";
} else {
    echo "Some items could NOT be changed (" . count($failures) . "):\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    echo "\nThis usually means those files are owned by a different user than\n";
    echo "PHP is running as — contact your host's support if orders.json or\n";
    echo "settings.json show up in this list, since those are the ones that\n";
    echo "must be writable.\n";
}

echo "\nNow test placing a real order on the site, then DELETE THIS FILE.\n";
