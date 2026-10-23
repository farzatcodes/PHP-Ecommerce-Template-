<?php
include_once 'includes/header.php';
require_once __DIR__ . '/../includes/categories.php';

$productsFile = '../products.json';
$products = file_exists($productsFile) ? (json_decode(file_get_contents($productsFile), true) ?: []) : [];

$notice = '';
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $categories = loadCategories();

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            $slug = categorySlug($name);
            $exists = false;
            foreach ($categories as $c) { if ($c['slug'] === $slug) { $exists = true; break; } }
            if ($exists) {
                $error = 'A category with that name/slug already exists.';
            } else {
                $categories[] = ['slug' => $slug, 'name' => $name];
                saveCategories($categories);
                $notice = 'Category "' . htmlspecialchars($name) . '" added.';
            }
        }
    } elseif ($action === 'rename') {
        $slug = $_POST['slug'] ?? '';
        $newName = trim($_POST['name'] ?? '');
        if ($newName === '') {
            $error = 'Category name cannot be empty.';
        } else {
            $found = false;
            foreach ($categories as &$c) {
                if ($c['slug'] === $slug) { $c['name'] = $newName; $found = true; break; }
            }
            unset($c);
            if ($found) {
                saveCategories($categories);
                $notice = 'Category renamed to "' . htmlspecialchars($newName) . '".';
            } else {
                $error = 'Category not found.';
            }
        }
    } elseif ($action === 'delete') {
        $slug = $_POST['slug'] ?? '';
        $inUse = countProductsInCategory($slug, $products);
        if ($inUse > 0) {
            $error = 'Cannot delete "' . htmlspecialchars(categoryName($slug, $categories)) . '" — ' . $inUse . ' product(s) still use it. Move them first from the Products page (bulk move).';
        } else {
            $categories = array_values(array_filter($categories, function($c) use ($slug) { return $c['slug'] !== $slug; }));
            saveCategories($categories);
            $notice = 'Category deleted.';
        }
    }

    // Refresh product counts after any change
    $products = file_exists($productsFile) ? (json_decode(file_get_contents($productsFile), true) ?: []) : [];
}

$categories = loadCategories();
?>

<div class="mb-6 flex items-center justify-between">
    <h2 class="text-2xl font-bold text-slate-800">Manage Categories</h2>
    <a href="products.php" class="text-sm text-brand-600 hover:underline"><i class="fas fa-arrow-left mr-1"></i>Back to Products</a>
</div>

<?php if ($notice): ?>
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-check-circle mr-1.5"></i><?= $notice ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-exclamation-circle mr-1.5"></i><?= $error ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Add New Category -->
    <div class="lg:col-span-1">
        <div class="wp-card p-4">
            <h2 class="font-semibold text-[14px] mb-3 border-b pb-2">Add New Category</h2>
            <form method="POST" class="space-y-3">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-[12px] mb-1">Category Name</label>
                    <input type="text" name="name" class="wp-input" placeholder="e.g. Home Decor" required>
                </div>
                <button type="submit" class="wp-button w-full py-2 text-sm font-bold">
                    <i class="fas fa-plus mr-1"></i> Add Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 text-xs font-semibold uppercase tracking-wider">
                            <th class="p-4">Name</th>
                            <th class="p-4">Slug</th>
                            <th class="p-4 text-center">Products</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="p-8 text-center text-slate-500">No categories yet. Add your first one!</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <?php $count = countProductsInCategory($cat['slug'], $products); ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors" id="row-<?= htmlspecialchars($cat['slug']) ?>">
                                    <td class="p-4 font-bold text-slate-900">
                                        <span class="view-name"><?= htmlspecialchars($cat['name']) ?></span>
                                        <form method="POST" class="edit-name hidden flex items-center gap-2">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="rename">
                                            <input type="hidden" name="slug" value="<?= htmlspecialchars($cat['slug']) ?>">
                                            <input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="wp-input py-1" required>
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800 text-sm" title="Save"><i class="fas fa-check"></i></button>
                                        </form>
                                    </td>
                                    <td class="p-4 text-xs text-slate-500 font-mono"><?= htmlspecialchars($cat['slug']) ?></td>
                                    <td class="p-4 text-center">
                                        <span class="inline-block bg-slate-100 text-slate-700 text-xs font-bold px-2.5 py-1 rounded-full"><?= $count ?></span>
                                    </td>
                                    <td class="p-4 text-right space-x-2 whitespace-nowrap">
                                        <button type="button" onclick="toggleEdit('<?= htmlspecialchars($cat['slug']) ?>')" class="inline-block text-brand-600 hover:text-brand-800 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg transition" title="Rename">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="inline-block" onsubmit="return confirm('Delete category &quot;<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>&quot;?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="slug" value="<?= htmlspecialchars($cat['slug']) ?>">
                                            <button type="submit" <?= $count > 0 ? 'disabled title="Move products out of this category first"' : 'title="Delete"' ?> class="inline-block text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-lg transition <?= $count > 0 ? 'opacity-40 cursor-not-allowed' : '' ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="text-xs text-slate-400 mt-3">Categories with products can't be deleted directly — use bulk-move on the Products page first.</p>
    </div>
</div>

<script>
function toggleEdit(slug) {
    const row = document.getElementById('row-' + CSS.escape(slug));
    row.querySelector('.view-name').classList.toggle('hidden');
    row.querySelector('.edit-name').classList.toggle('hidden');
    row.querySelector('.edit-name').classList.toggle('flex');
}
</script>

<?php include_once 'includes/footer.php'; ?>
