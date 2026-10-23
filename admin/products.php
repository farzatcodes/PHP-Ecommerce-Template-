<?php
require_once __DIR__ . '/../includes/categories.php';
$jsonFile = '../products.json';
$products = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) ?? [] : [];
$categories = loadCategories();

// Active vs archived view (archived products are hidden from the storefront)
$view = ($_GET['view'] ?? '') === 'archived' ? 'archived' : 'active';
$activeCount   = count(array_filter($products, fn($p) => empty($p['archived'])));
$archivedCount = count($products) - $activeCount;
$products = array_values(array_filter($products, fn($p) => ($view === 'archived') === !empty($p['archived'])));
include 'includes/header.php';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-slate-800">Products List</h2>
    <div class="flex items-center gap-2">
        <a href="categories.php" class="bg-white border border-slate-300 text-slate-700 px-4 py-2.5 rounded-lg shadow-sm hover:bg-slate-50 transition-colors font-medium flex items-center gap-2">
            <i class="fas fa-sitemap"></i> Categories
        </a>
        <?php if ($view === 'active' && $activeCount > 0): ?>
        <button type="submit" form="archiveAllForm"
                onclick="return confirm('Archive ALL <?= $activeCount ?> products?\n\nThey will be hidden from the storefront but not deleted. You can restore them any time from the Archived tab.');"
                class="bg-amber-500 text-white px-4 py-2.5 rounded-lg shadow hover:bg-amber-600 transition-colors font-bold flex items-center gap-2">
            <i class="fas fa-box-archive"></i> Archive All
        </button>
        <?php elseif ($view === 'archived' && $archivedCount > 0): ?>
        <button type="submit" form="restoreAllForm"
                onclick="return confirm('Restore all <?= $archivedCount ?> archived products to the storefront?');"
                class="bg-emerald-600 text-white px-4 py-2.5 rounded-lg shadow hover:bg-emerald-700 transition-colors font-bold flex items-center gap-2">
            <i class="fas fa-rotate-left"></i> Restore All
        </button>
        <?php endif; ?>
        <a href="add_product.php" class="bg-brand-600 text-white px-5 py-2.5 rounded-lg shadow hover:bg-brand-700 transition-colors font-bold flex items-center gap-2">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
</div>

<!-- Active / Archived tabs -->
<div class="flex gap-2 mb-4">
    <a href="products.php" class="px-4 py-2 rounded-lg text-sm font-bold border <?= $view==='active' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' ?>">
        Active <span class="opacity-70">(<?= $activeCount ?>)</span>
    </a>
    <a href="products.php?view=archived" class="px-4 py-2 rounded-lg text-sm font-bold border <?= $view==='archived' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50' ?>">
        <i class="fas fa-box-archive mr-1"></i>Archived <span class="opacity-70">(<?= $archivedCount ?>)</span>
    </a>
</div>

<?php if (isset($_GET['archived'])): ?>
<div class="mb-4 bg-amber-50 border-l-4 border-amber-500 text-amber-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-box-archive mr-1.5"></i><?= (int)$_GET['archived'] ?> product(s) archived and hidden from the storefront. <a href="products.php?view=archived" class="underline">View archived</a>
</div>
<?php endif; ?>
<?php if (isset($_GET['restored'])): ?>
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-check-circle mr-1.5"></i><?= (int)$_GET['restored'] ?> product(s) restored to the storefront.
</div>
<?php endif; ?>
<?php if (isset($_GET['archive_error'])): ?>
<div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm font-medium px-4 py-3 rounded-xl">
    Could not update products. Check file permissions on products.json.
</div>
<?php endif; ?>

<?php if (isset($_GET['moved'])): ?>
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-check-circle mr-1.5"></i>Selected products were moved.
</div>
<?php endif; ?>

<!-- Bulk Actions Toolbar -->
<form method="POST" action="bulk_move_category.php" id="bulkMoveForm">
    <?= csrf_field() ?>
    <div id="bulkToolbar" class="hidden mb-4 bg-slate-800 text-white rounded-xl px-4 py-3 flex flex-wrap items-center gap-3">
        <span class="text-sm font-medium"><span id="bulkCount">0</span> selected</span>
        <select name="target_category" class="text-sm rounded-lg px-3 py-1.5 text-slate-800" required>
            <option value="">Move to category...</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-brand-600 text-white text-sm font-bold px-4 py-1.5 rounded-lg hover:bg-brand-700 transition-colors">
            Move Selected
        </button>
        <button type="button" onclick="clearSelection()" class="text-sm text-slate-300 hover:text-white ml-auto">Clear</button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                        <th class="p-4 w-10"><input type="checkbox" id="selectAll" class="w-4 h-4 rounded-lg cursor-pointer"></th>
                        <th class="p-4 w-16">Image</th>
                        <th class="p-4">Name & Category</th>
                        <th class="p-4">Price</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-500"><?= $view === 'archived' ? 'No archived products.' : 'No products found. Add your first product!' ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_reverse($products) as $product): ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="p-4">
                                    <input type="checkbox" name="product_ids[]" value="<?php echo htmlspecialchars($product['id']); ?>" class="row-checkbox w-4 h-4 rounded-lg cursor-pointer">
                                </td>
                                <td class="p-4">
                                    <img src="..<?php echo htmlspecialchars($product['image_url']); ?>" class="w-12 h-12 object-cover rounded-lg border border-slate-200 bg-white" alt="Product Image">
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                    <div class="text-xs text-slate-500 uppercase mt-0.5"><?php echo htmlspecialchars(categoryName($product['category'] ?? '', $categories)); ?></div>
                                </td>
                                <td class="p-4 font-bold text-slate-800">
                                    ৳<?php echo htmlspecialchars($product['price']); ?>
                                    <?php if(!empty($product['old_price'])): ?>
                                        <span class="text-xs text-slate-400 line-through font-normal ml-1">৳<?php echo htmlspecialchars($product['old_price']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right space-x-2 whitespace-nowrap">
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="inline-block text-brand-600 hover:text-brand-800 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg transition" title="Edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($view === 'archived'): ?>
                                    <button type="submit" form="restoreOneForm" name="id" value="<?= htmlspecialchars($product['id']) ?>" class="inline-block text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition" title="Restore">
                                        <i class="fas fa-rotate-left"></i> Restore
                                    </button>
                                    <?php else: ?>
                                    <button type="submit" form="archiveOneForm" name="id" value="<?= htmlspecialchars($product['id']) ?>" class="inline-block text-amber-700 bg-amber-50 hover:bg-amber-100 px-3 py-1.5 rounded-lg transition" title="Archive">
                                        <i class="fas fa-box-archive"></i> Archive
                                    </button>
                                    <?php endif; ?>
                                    <a href="delete_product.php?id=<?php echo $product['id']; ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="inline-block text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition" title="Delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- Archive forms (kept outside the bulk-move form; buttons attach via form="...") -->
<form id="archiveAllForm" method="POST" action="archive_products.php" class="hidden"><?= csrf_field() ?><input type="hidden" name="action" value="archive_all"></form>
<form id="restoreAllForm" method="POST" action="archive_products.php" class="hidden"><?= csrf_field() ?><input type="hidden" name="action" value="restore_all"></form>
<form id="archiveOneForm" method="POST" action="archive_products.php" class="hidden"><?= csrf_field() ?><input type="hidden" name="action" value="archive"></form>
<form id="restoreOneForm" method="POST" action="archive_products.php" class="hidden"><?= csrf_field() ?><input type="hidden" name="action" value="restore"></form>

<script>
(function() {
    const selectAll = document.getElementById('selectAll');
    const toolbar = document.getElementById('bulkToolbar');
    const bulkCount = document.getElementById('bulkCount');

    function rowCheckboxes() {
        return Array.from(document.querySelectorAll('.row-checkbox'));
    }

    function refreshToolbar() {
        const checked = rowCheckboxes().filter(cb => cb.checked);
        bulkCount.textContent = checked.length;
        toolbar.classList.toggle('hidden', checked.length === 0);
    }

    selectAll.addEventListener('change', function() {
        rowCheckboxes().forEach(cb => cb.checked = selectAll.checked);
        refreshToolbar();
    });

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) refreshToolbar();
    });

    window.clearSelection = function() {
        rowCheckboxes().forEach(cb => cb.checked = false);
        selectAll.checked = false;
        refreshToolbar();
    };

    document.getElementById('bulkMoveForm').addEventListener('submit', function(e) {
        const checked = rowCheckboxes().filter(cb => cb.checked);
        if (checked.length === 0) {
            e.preventDefault();
            return;
        }
        if (!confirm('Move ' + checked.length + ' product(s) to the selected category?')) {
            e.preventDefault();
        }
    });
})();
</script>

<?php include 'includes/footer.php'; ?>