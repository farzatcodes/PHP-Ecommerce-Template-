<?php
include_once 'includes/header.php';
require_once __DIR__ . '/../includes/categories.php';
$jsonFile = '../products.json';
$categories = loadCategories();

function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'product-' . time();
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify(); // Bug #16

    // Bug #1: open with exclusive lock for safe concurrent writes
    $fp = fopen($jsonFile, 'c+');
    flock($fp, LOCK_EX);
    $products = json_decode(stream_get_contents($fp), true) ?: [];

    // Bug #8: reject duplicate product IDs
    $newId = trim($_POST['id'] ?? '');
    foreach ($products as $p) {
        if ($p['id'] === $newId) {
            flock($fp, LOCK_UN);
            fclose($fp);
            $dupError = 'Product ID "' . htmlspecialchars($newId) . '" already exists. Choose a unique ID.';
            // fall through to re-render the form with error
            goto render_form;
        }
    }

    $folderName = createSlug($_POST['name']);
    $uploadDir = '../assets/products/' . $folderName . '/';
    $jsonPathPrefix = '/assets/products/' . $folderName . '/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $mainImageUrl = '';
    $galleryUrls = [];

    // Main Image
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['main_image']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['main_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $newFileName = 'main_' . time() . '.' . $ext;
            if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                $mainImageUrl = $jsonPathPrefix . $newFileName;
            }
        }
    }

    // Gallery Images
    if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
        $fileCount = count($_FILES['gallery']['name']);
        $maxImages = min($fileCount, 5);
        
        for ($i = 0; $i < $maxImages; $i++) {
            if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['gallery']['tmp_name'][$i];
                $ext = strtolower(pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $newFileName = 'gallery_' . $i . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                        $galleryUrls[] = $jsonPathPrefix . $newFileName;
                    }
                }
            }
        }
    }
    
    $newProduct = [
        'id' => $_POST['id'],
        'name' => $_POST['name'],
        'category' => $_POST['category'],
        'price' => $_POST['price'],
        'old_price' => empty($_POST['old_price']) ? null : $_POST['old_price'],
        'image_url' => $mainImageUrl,
        'gallery' => $galleryUrls,
        'description' => $_POST['description'],
        'reviews' => [] 
    ];
    
    $products[] = $newProduct;

    // Write back under the same lock (Bug #1)
    $json = json_encode($products, JSON_PRETTY_PRINT);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    header('Location: products.php');
    exit;
}

render_form:
?>

<div class="mb-4 flex items-center gap-3">
    <a href="products.php" class="text-brand-600 hover:underline text-sm">&larr; Back to Products</a>
    <h2 class="text-2xl font-normal text-slate-900">Add New Product</h2>
</div>

<?php if (!empty($dupError)): ?>
<div class="mb-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-sm font-medium px-4 py-3 rounded-xl">
    <i class="fas fa-exclamation-circle mr-1.5"></i><?= $dupError ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <?= csrf_field() ?>
    <div class="col-span-2 space-y-4">
        <div class="wp-card p-4">
            <div class="mb-4">
                <label class="block text-[13px] font-semibold mb-1">Product Name</label>
                <input type="text" name="name" class="wp-input py-2 text-lg" required>
            </div>
            <div>
                <label class="block text-[13px] font-semibold mb-1">Description</label>
                <div id="descToolbar" class="flex items-center gap-1 border border-slate-300 border-b-0 rounded-t bg-slate-50 p-1.5">
                    <button type="button" data-cmd="bold" class="desc-tool-btn" title="Bold"><i class="fas fa-bold"></i></button>
                    <button type="button" data-cmd="italic" class="desc-tool-btn" title="Italic"><i class="fas fa-italic"></i></button>
                    <button type="button" data-cmd="insertUnorderedList" class="desc-tool-btn" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                    <button type="button" data-cmd="insertOrderedList" class="desc-tool-btn" title="Numbered list"><i class="fas fa-list-ol"></i></button>
                    <span class="w-px h-5 bg-slate-300 mx-1"></span>
                    <button type="button" id="descImageBtn" class="desc-tool-btn" title="Insert image"><i class="fas fa-image"></i></button>
                    <input type="file" id="descImageInput" accept="image/*" class="hidden">
                    <span class="text-[11px] text-slate-400 ml-1">or paste / drag an image in</span>
                    <span id="descUploadStatus" class="text-xs ml-2"></span>
                </div>
                <div id="descEditor" contenteditable="true" data-placeholder="Write a product description... you can paste or drag images in directly." class="wp-input rounded-t-none py-2"></div>
                <textarea name="description" id="descTextarea" class="hidden"></textarea>
            </div>
        </div>
        
        <div class="wp-card p-4">
            <h2 class="font-semibold text-[14px] mb-3 border-b pb-2">Product Images</h2>
            
            <div class="mb-5">
                <label class="block text-[13px] font-bold mb-2">Main Product Image <span class="text-rose-500">*</span></label>
                <input type="file" name="main_image" accept="image/jpeg, image/png, image/webp, image/gif" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-600 hover:file:bg-brand-100 border border-slate-300 rounded-lg p-1 cursor-pointer" required>
            </div>

            <div>
                <label class="block text-[13px] font-bold mb-2">Gallery Images (Max 5)</label>
                <input type="file" name="gallery[]" accept="image/jpeg, image/png, image/webp, image/gif" multiple class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-50 file:text-slate-700 hover:file:bg-slate-100 border border-slate-300 rounded-lg p-1 cursor-pointer">
            </div>
        </div>
    </div>
    
    <div class="space-y-4">
        <div class="wp-card p-4">
            <h2 class="font-semibold text-[14px] mb-3 border-b pb-2">Publish</h2>
            <button type="submit" class="wp-button w-full py-2 text-base font-bold">Save Product</button>
        </div>
        
        <div class="wp-card p-4">
            <h2 class="font-semibold text-[14px] mb-3 border-b pb-2">Product Data</h2>
            <div class="mb-3">
                <label class="block text-[12px] mb-1">Product ID (Unique)</label>
                <input type="text" name="id" class="wp-input" placeholder="e.g. 105" required>
            </div>
            <div class="mb-3">
                <label class="block text-[12px] mb-1 flex items-center justify-between">
                    <span>Category</span>
                    <a href="categories.php" class="text-[11px] text-brand-600 hover:underline font-normal">+ Manage categories</a>
                </label>
                <select name="category" class="wp-input" required>
                    <?php if (empty($categories)): ?>
                        <option value="">No categories yet — add one first</option>
                    <?php endif; ?>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="block text-[12px] mb-1">Current Price (৳)</label>
                <input type="number" name="price" class="wp-input" required>
            </div>
            <div class="mb-3">
                <label class="block text-[12px] mb-1">Old Price (৳) [Optional]</label>
                <input type="number" name="old_price" class="wp-input" placeholder="Leave blank if no discount">
            </div>
        </div>
    </div>
</form>

<script src="includes/description-editor.js"></script>

<?php include_once 'includes/footer.php'; ?>