<?php
$jsonFile = '../landing_pages.json';
$landingPages = [];

// Initialize if doesn't exist
if (!file_exists($jsonFile)) {
    file_put_contents($jsonFile, json_encode([]));
} else {
    $landingPages = json_decode(file_get_contents($jsonFile), true) ?? [];
}

include 'includes/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-slate-800">Landing Pages</h2>
    <a href="manage_landing.php" class="bg-brand-600 text-white px-4 py-2 rounded-lg shadow hover:bg-brand-700 transition-colors">
        <i class="fas fa-plus mr-2"></i> Create Landing Page
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-sm">
                <th class="p-4">Page Title</th>
                <th class="p-4">URL Slug</th>
                <th class="p-4">Theme Color</th>
                <th class="p-4 text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($landingPages)): ?>
                <tr>
                    <td colspan="4" class="p-8 text-center text-slate-500">
                        <i class="fas fa-file-alt text-4xl mb-3 text-slate-300 block"></i>
                        No landing pages created yet. Click "Create Landing Page" to start!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach (array_reverse($landingPages) as $page): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50">
                        <td class="p-4 font-medium text-slate-800"><?php echo htmlspecialchars($page['page_title']); ?></td>
                        <td class="p-4 text-slate-600">
                            <span class="bg-slate-100 px-2 py-1 rounded-lg text-sm">/landing/<?php echo htmlspecialchars($page['slug']); ?>/</span>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full border border-slate-300" style="background-color: <?php echo htmlspecialchars($page['theme_color']); ?>;"></div>
                                <span class="text-xs text-slate-500 uppercase"><?php echo htmlspecialchars($page['theme_color']); ?></span>
                            </div>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <?php 
                                // Generate full URL based on current server
                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                                $host = $_SERVER['HTTP_HOST'];
                                $basePath = str_replace('/admin/landing_pages.php', '', $_SERVER['PHP_SELF']);
                                $fullUrl = $protocol . $host . $basePath . '/landing/' . $page['slug'] . '/';
                            ?>
                            <button onclick="copyLink('<?php echo $fullUrl; ?>')" class="text-emerald-500 hover:text-emerald-700 bg-emerald-50 px-2 py-1 rounded-lg transition" title="Copy Live Link">
                                <i class="fas fa-link"></i> Copy
                            </button>
                            <a href="<?php echo $fullUrl; ?>" target="_blank" class="text-brand-500 hover:text-brand-700 bg-brand-50 px-2 py-1 rounded-lg transition" title="View Live">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="manage_landing.php?id=<?php echo $page['id']; ?>" class="text-brand-500 hover:text-brand-700 bg-brand-50 px-2 py-1 rounded-lg transition" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete_landing.php?id=<?php echo $page['id']; ?>" onclick="return confirm('Are you sure you want to delete this landing page? The deployed folder will also be removed.');" class="text-rose-500 hover:text-rose-700 bg-rose-50 px-2 py-1 rounded-lg transition" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function copyLink(url) {
    // Create a temporary input to copy the text
    const tempInput = document.createElement("input");
    tempInput.value = url;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    alert("Landing page link copied to clipboard:\n" + url);
}
</script>

<?php include 'includes/footer.php'; ?>