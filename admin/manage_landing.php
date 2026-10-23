<?php
$jsonFile = '../landing_pages.json';
$landingPages = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) ?? [] : [];

$productsFile = '../products.json';
$products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) ?? [] : [];

$pageId = isset($_GET['id']) ? $_GET['id'] : null;
$pageData = null;

if ($pageId) {
    foreach ($landingPages as $lp) {
        if ($lp['id'] === $pageId) {
            $pageData = $lp;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?: uniqid('lp_');
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));

    // Look up the existing record by the posted id (NOT $pageData, which is only
    // populated from $_GET['id'] — the edit form's action has no query string, so
    // $_GET['id'] is empty on submit and $pageData would be null here, wiping out
    // hero_image/gallery_images on every save that doesn't also touch those fields).
    $existingData = null;
    foreach ($landingPages as $lp) {
        if ($lp['id'] === $id) {
            $existingData = $lp;
            break;
        }
    }

    $newData = [
        'id'             => $id,
        'slug'           => $slug,
        'page_title'     => $_POST['page_title'],
        'theme_color'    => $_POST['theme_color'],
        'product_id'     => $_POST['product_id'],
        'contact_phone'  => $_POST['contact_phone']  ?? '',
        'ticker_text'    => $_POST['ticker_text']    ?? '',
        'top_subheading' => $_POST['top_subheading'] ?? '',
        'hero_heading'   => $_POST['hero_heading']   ?? '',
        'hero_subheading'=> $_POST['hero_subheading']?? '',
        'offer_text'     => $_POST['offer_text']     ?? '',
        'countdown_date' => $_POST['countdown_date'] ?? '',
        'features'       => explode("\n", str_replace("\r", "", trim($_POST['features'] ?? ''))),
        'hero_image'     => $existingData['hero_image']     ?? '',
        'gallery_images' => $existingData['gallery_images'] ?? [],
    ];

    // Parse packages from parallel arrays
    $packages = [];
    foreach ($_POST['pkg_label'] ?? [] as $i => $label) {
        if (trim($label) === '') continue;
        $packages[] = [
            'label'    => trim($label),
            'quantity' => max(1, (int)($_POST['pkg_qty'][$i]   ?? 1)),
            'price'    => max(0, (int)($_POST['pkg_price'][$i] ?? 0)),
            'badge'    => trim($_POST['pkg_badge'][$i] ?? ''),
        ];
    }
    $newData['packages'] = $packages;

    $uploadDir = '../assets/landing/' . $slug . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Only allow gallery-picked paths that actually point inside our own upload folders
    $isValidMediaPath = function ($path) {
        return is_string($path) && (str_starts_with($path, '/assets/products/') || str_starts_with($path, '/assets/landing/'));
    };

    // Hero image — picked from the media gallery takes priority, then a fresh upload
    if (!empty($_POST['hero_image_picked']) && $isValidMediaPath($_POST['hero_image_picked'])) {
        $newData['hero_image'] = $_POST['hero_image_picked'];
    }
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
        $ext      = pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION);
        $filename = 'hero_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $uploadDir . $filename)) {
            $newData['hero_image'] = '/assets/landing/' . $slug . '/' . $filename;
        }
    }

    // Gallery images — combine picker selections with fresh uploads (multiple)
    if (!empty($_POST['replace_gallery'])) {
        $newData['gallery_images'] = [];
    }
    if (!empty($_POST['gallery_images_picked']) && is_array($_POST['gallery_images_picked'])) {
        foreach ($_POST['gallery_images_picked'] as $pickedUrl) {
            if ($isValidMediaPath($pickedUrl)) {
                $newData['gallery_images'][] = $pickedUrl;
            }
        }
    }
    if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_OK) {
                $ext      = pathinfo($_FILES['gallery_images']['name'][$i], PATHINFO_EXTENSION);
                $filename = 'gallery_' . time() . '_' . $i . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                    $newData['gallery_images'][] = '/assets/landing/' . $slug . '/' . $filename;
                }
            }
        }
    }
    if (!empty($newData['gallery_images'])) {
        $newData['gallery_images'] = array_values(array_unique($newData['gallery_images']));
    }

    // Save to JSON
    $updated = false;
    foreach ($landingPages as $key => $lp) {
        if ($lp['id'] === $id) {
            if ($lp['slug'] !== $slug) {
                $oldDir = '../landing/' . $lp['slug'];
                if (is_dir($oldDir)) { @unlink($oldDir . '/index.php'); @rmdir($oldDir); }
            }
            $landingPages[$key] = $newData;
            $updated = true;
            break;
        }
    }
    if (!$updated) {
        $landingPages[] = $newData;
    }
    file_put_contents($jsonFile, json_encode($landingPages, JSON_PRETTY_PRINT));

    // Deploy
    $deployDir = '../landing/' . $slug;
    if (!file_exists($deployDir)) {
        mkdir($deployDir, 0777, true);
    }
    $indexContent = "<?php\n\$landing_slug = '" . $slug . "';\nrequire_once '../../render_landing.php';\n?>";
    file_put_contents($deployDir . '/index.php', $indexContent);

    header('Location: landing_pages.php');
    exit;
}

include 'includes/header.php';
?>

<div class="mb-6 flex items-center justify-between">
    <h2 class="text-2xl font-bold text-slate-800"><?php echo $pageData ? 'Edit Landing Page' : 'Create Landing Page'; ?></h2>
    <a href="landing_pages.php" class="text-slate-500 hover:text-slate-700 text-sm">&larr; Back to List</a>
</div>

<form action="manage_landing.php" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-5xl">
    <input type="hidden" name="id" value="<?php echo $pageData ? $pageData['id'] : ''; ?>">

    <!-- 1. General Settings -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="bg-brand-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">1</span> General Settings</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Page Title (Browser Tab)</label>
                <input type="text" name="page_title" required value="<?php echo $pageData ? htmlspecialchars($pageData['page_title']) : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="e.g. Digital Scale Offer">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">URL Slug</label>
                <input type="text" name="slug" required value="<?php echo $pageData ? htmlspecialchars($pageData['slug']) : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="e.g. digital-scale-offer">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Theme Color</label>
                <div class="flex items-center gap-2">
                    <input type="color" name="theme_color" value="<?php echo $pageData ? htmlspecialchars($pageData['theme_color']) : '#DB1456'; ?>" class="h-10 w-14 border border-slate-300 rounded-lg cursor-pointer">
                    <span class="text-xs text-slate-500">Primary Color</span>
                </div>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-bold text-slate-700 mb-1">Select Product</label>
                <select name="product_id" required class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none bg-white">
                    <option value="">-- Choose a Product --</option>
                    <?php foreach($products as $prod): ?>
                        <option value="<?php echo $prod['id']; ?>" <?php echo ($pageData && $pageData['product_id'] == $prod['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prod['name']) . ' (৳' . $prod['price'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-slate-500 mt-1">Product details (price, image) load automatically on the page.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Contact Phone</label>
                <input type="text" name="contact_phone" value="<?php echo $pageData ? htmlspecialchars($pageData['contact_phone'] ?? '') : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none">
            </div>
        </div>
    </div>

    <!-- 2. Page Content -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="bg-brand-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">2</span> Page Content &amp; Copy</h2>
        </div>
        <div class="p-6 space-y-5">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Top Ticker Text</label>
                    <input type="text" name="ticker_text" value="<?php echo $pageData ? htmlspecialchars($pageData['ticker_text'] ?? '') : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="e.g. Offer ends soon!">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Top Subheading</label>
                    <input type="text" name="top_subheading" value="<?php echo $pageData ? htmlspecialchars($pageData['top_subheading'] ?? '') : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="e.g. Special Eid Offer">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Main Hero Heading (Large Text)</label>
                <textarea name="hero_heading" rows="2" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="Your big bold headline here..."><?php echo $pageData ? htmlspecialchars($pageData['hero_heading'] ?? '') : ''; ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Hero Subheading</label>
                <textarea name="hero_subheading" rows="2" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="Smaller supporting text under the heading..."><?php echo $pageData ? htmlspecialchars($pageData['hero_subheading'] ?? '') : ''; ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Offer Highlight Text</label>
                    <input type="text" name="offer_text" value="<?php echo $pageData ? htmlspecialchars($pageData['offer_text'] ?? '') : ''; ?>" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none" placeholder="e.g. Unbelievable discount! Cash on delivery available">
                    <p class="text-xs text-slate-500 mt-1">Shown inside the red offer box. Leave blank to hide it.</p>
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Countdown End Date &amp; Time</label>
                    <input type="datetime-local" name="countdown_date"
                        value="<?php echo $pageData ? htmlspecialchars($pageData['countdown_date'] ?? '') : ''; ?>"
                        class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none bg-white">
                    <p class="text-xs text-slate-500 mt-1">Live countdown timer in the offer box. Leave blank to hide timer.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- 3. Features & Media -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="bg-brand-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">3</span> Features &amp; Media</h2>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-1">Key Features / Benefits <span class="font-normal text-slate-400">(one per line)</span></label>
                <textarea name="features" rows="7" class="w-full border border-slate-300 rounded-lg p-2 focus:border-brand-600 focus:outline-none font-mono text-sm" placeholder="LCD Display&#10;Easy to carry&#10;Weighs from 10g to 50kg"><?php echo $pageData ? htmlspecialchars(implode("\n", $pageData['features'] ?? [])) : ''; ?></textarea>
                <p class="text-xs text-slate-500 mt-1">Appear with green checkmarks on the landing page.</p>
            </div>

            <div class="space-y-4">
                <div class="border border-slate-200 rounded-xl p-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Hero Image <span class="font-normal text-slate-400">(optional)</span></label>
                    <div id="heroImagePreview" class="flex flex-wrap gap-2 mb-2">
                        <?php if($pageData && !empty($pageData['hero_image'])): ?>
                            <img src="..<?php echo htmlspecialchars($pageData['hero_image']); ?>" class="h-24 object-contain bg-slate-50 rounded-lg border p-1">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="hero_image_picked" id="heroImagePicked" value="">
                    <div class="flex items-center gap-2">
                        <input type="file" name="hero_image" accept="image/*" class="flex-1 text-sm p-2 border border-slate-200 rounded-lg bg-slate-50">
                        <button type="button" onclick="openMediaPicker('hero')" class="shrink-0 text-sm font-bold text-slate-800 hover:text-white bg-slate-100 hover:bg-brand-600 border border-slate-300 px-3 py-2 rounded-lg transition whitespace-nowrap">
                            <i class="fas fa-images mr-1"></i>Choose from Gallery
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">If blank, the product's default image is used.</p>
                </div>

                <div class="border border-slate-200 rounded-xl p-4">
                    <label class="block text-sm font-bold text-slate-700 mb-2">Gallery / Review Screenshots</label>
                    <div id="galleryImagesPreview" class="flex flex-wrap gap-2 mb-3">
                        <?php if(!empty($pageData['gallery_images'])): ?>
                            <?php foreach($pageData['gallery_images'] as $gi): ?>
                                <img src="..<?php echo htmlspecialchars($gi); ?>" class="h-16 w-16 object-cover rounded-lg border bg-white">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div id="galleryPickedInputs"></div>
                    <?php if(!empty($pageData['gallery_images'])): ?>
                        <label class="flex items-center gap-2 text-xs text-rose-600 font-bold mb-2 cursor-pointer">
                            <input type="checkbox" name="replace_gallery" value="1" class="w-4 h-4">
                            Replace all existing gallery images
                        </label>
                    <?php endif; ?>
                    <div class="flex items-center gap-2">
                        <input type="file" name="gallery_images[]" accept="image/*" multiple class="flex-1 text-sm p-2 border border-slate-200 rounded-lg bg-slate-50">
                        <button type="button" onclick="openMediaPicker('gallery')" class="shrink-0 text-sm font-bold text-slate-800 hover:text-white bg-slate-100 hover:bg-brand-600 border border-slate-300 px-3 py-2 rounded-lg transition whitespace-nowrap">
                            <i class="fas fa-images mr-1"></i>Choose from Gallery
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">Select multiple images, or add existing product photos from the gallery. Displayed as a swipeable carousel on the page.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- 4. Packages -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 flex items-center gap-2">
                <span class="bg-brand-600 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center">4</span>
                Pricing Packages
            </h2>
            <p class="text-xs text-slate-500">Customer picks one package at checkout. Leave empty to show the product's default price.</p>
        </div>
        <div class="p-6">

            <div id="pkg-rows" class="space-y-3 mb-4">
                <?php
                $existingPkgs = $pageData['packages'] ?? [];
                if (empty($existingPkgs)) $existingPkgs = []; // start empty, JS adds first row
                foreach ($existingPkgs as $pi => $pkg):
                ?>
                <div class="pkg-row grid grid-cols-12 gap-2 items-center bg-slate-50 border border-slate-200 rounded-xl p-3">
                    <div class="col-span-4">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Label</label>
                        <input type="text" name="pkg_label[]" value="<?= htmlspecialchars($pkg['label']) ?>"
                            placeholder="e.g. 2 Pieces" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Qty</label>
                        <input type="number" name="pkg_qty[]" value="<?= (int)$pkg['quantity'] ?>" min="1"
                            class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Price (৳)</label>
                        <input type="number" name="pkg_price[]" value="<?= (int)$pkg['price'] ?>" min="0"
                            class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Badge <span class="font-normal">(opt.)</span></label>
                        <input type="text" name="pkg_badge[]" value="<?= htmlspecialchars($pkg['badge'] ?? '') ?>"
                            placeholder="Best Value" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
                    </div>
                    <div class="col-span-1 flex items-end pb-0.5">
                        <button type="button" onclick="this.closest('.pkg-row').remove()"
                            class="w-full flex items-center justify-center h-9 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-lg border border-rose-200 transition">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="button" onclick="addPkgRow()"
                class="flex items-center gap-2 text-sm font-bold text-brand-600 hover:text-brand-800 bg-brand-50 hover:bg-brand-100 border border-brand-200 px-4 py-2 rounded-xl transition">
                <i class="fas fa-plus"></i> Add Package
            </button>

            <p class="text-xs text-slate-400 mt-3">
                <i class="fas fa-info-circle"></i>
                Packages are shown as selectable radio cards on the landing page. First package is selected by default.
            </p>
        </div>
    </div>

    <script>
    function addPkgRow() {
        const row = document.createElement('div');
        row.className = 'pkg-row grid grid-cols-12 gap-2 items-center bg-slate-50 border border-slate-200 rounded-xl p-3';
        row.innerHTML = `
            <div class="col-span-4">
                <label class="block text-xs font-bold text-slate-500 mb-1">Label</label>
                <input type="text" name="pkg_label[]" placeholder="e.g. 1 Piece"
                    class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold text-slate-500 mb-1">Qty</label>
                <input type="number" name="pkg_qty[]" value="1" min="1"
                    class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
            </div>
            <div class="col-span-3">
                <label class="block text-xs font-bold text-slate-500 mb-1">Price (৳)</label>
                <input type="number" name="pkg_price[]" value="" min="0" placeholder="0"
                    class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-bold text-slate-500 mb-1">Badge <span class="font-normal">(opt.)</span></label>
                <input type="text" name="pkg_badge[]" placeholder="Best Value"
                    class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
            </div>
            <div class="col-span-1 flex items-end pb-0.5">
                <button type="button" onclick="this.closest('.pkg-row').remove()"
                    class="w-full flex items-center justify-center h-9 bg-rose-50 hover:bg-rose-100 text-rose-500 rounded-lg border border-rose-200 transition">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>`;
        document.getElementById('pkg-rows').appendChild(row);
    }
    </script>

    <!-- Media Gallery Picker Modal -->
    <div id="mediaPickerOverlay" class="hidden fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-800"><i class="fas fa-images mr-2 text-brand-600"></i>Choose from Gallery</h3>
                <button type="button" onclick="closeMediaPicker()" class="text-slate-400 hover:text-slate-700 w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="px-5 py-3 border-b border-slate-100">
                <input type="text" id="mediaPickerSearch" placeholder="Search by filename..." class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:border-brand-600 focus:outline-none">
            </div>
            <div id="mediaPickerGrid" class="flex-1 overflow-y-auto p-5 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                <p class="col-span-full text-center text-slate-400 py-10">Loading images...</p>
            </div>
            <div class="flex items-center justify-between px-5 py-4 border-t border-slate-200 bg-slate-50">
                <span id="mediaPickerCount" class="text-xs text-slate-500">0 selected</span>
                <div class="flex gap-2">
                    <button type="button" onclick="closeMediaPicker()" class="text-sm font-bold text-slate-600 hover:text-slate-800 px-4 py-2 rounded-lg transition">Cancel</button>
                    <button type="button" onclick="confirmMediaPicker()" class="text-sm font-bold text-white bg-brand-600 hover:bg-brand-700 px-5 py-2 rounded-lg transition">Use Selected</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        let mediaCache = null;
        let pickerMode = 'hero'; // 'hero' = single select, 'gallery' = multi select
        let selectedUrls = [];

        window.openMediaPicker = function (mode) {
            pickerMode = mode;
            selectedUrls = [];
            document.getElementById('mediaPickerOverlay').classList.remove('hidden');
            document.getElementById('mediaPickerSearch').value = '';
            if (mediaCache) {
                renderMediaGrid(mediaCache);
            } else {
                fetch('list_media.php')
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        mediaCache = (data.success && data.images) ? data.images : [];
                        renderMediaGrid(mediaCache);
                    })
                    .catch(function () {
                        document.getElementById('mediaPickerGrid').innerHTML = '<p class="col-span-full text-center text-rose-500 py-10">Could not load images.</p>';
                    });
            }
        };

        window.closeMediaPicker = function () {
            document.getElementById('mediaPickerOverlay').classList.add('hidden');
        };

        function renderMediaGrid(images) {
            const grid = document.getElementById('mediaPickerGrid');
            if (!images.length) {
                grid.innerHTML = '<p class="col-span-full text-center text-slate-400 py-10">No images found yet. Upload a product or landing page image first.</p>';
                return;
            }
            grid.innerHTML = '';
            images.forEach(function (img) {
                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'media-cell relative border-2 border-transparent rounded-lg overflow-hidden hover:border-brand-600 transition group';
                cell.dataset.url = img.url;
                cell.innerHTML =
                    '<img src="..' + img.url + '" class="w-full h-20 object-cover bg-slate-100" loading="lazy">' +
                    '<span class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></span>' +
                    '<span class="check-badge absolute top-1 right-1 w-5 h-5 rounded-full bg-white border border-slate-300 hidden items-center justify-center text-brand-600 text-xs"><i class="fas fa-check"></i></span>' +
                    '<span class="absolute bottom-0 inset-x-0 bg-black/50 text-white text-[9px] px-1 py-0.5 truncate">' + img.source + '</span>';
                cell.addEventListener('click', function () { toggleSelect(cell, img.url); });
                grid.appendChild(cell);
            });
            updateCount();
        }

        function toggleSelect(cell, url) {
            const idx = selectedUrls.indexOf(url);
            if (idx > -1) {
                selectedUrls.splice(idx, 1);
                cell.classList.remove('border-brand-600');
                cell.querySelector('.check-badge').classList.add('hidden');
                cell.querySelector('.check-badge').classList.remove('flex');
            } else {
                if (pickerMode === 'hero') {
                    // single-select: clear any other selection first
                    selectedUrls = [];
                    document.querySelectorAll('#mediaPickerGrid .media-cell').forEach(function (c) {
                        c.classList.remove('border-brand-600');
                        c.querySelector('.check-badge').classList.add('hidden');
                        c.querySelector('.check-badge').classList.remove('flex');
                    });
                }
                selectedUrls.push(url);
                cell.classList.add('border-brand-600');
                cell.querySelector('.check-badge').classList.remove('hidden');
                cell.querySelector('.check-badge').classList.add('flex');
            }
            updateCount();
        }

        function updateCount() {
            document.getElementById('mediaPickerCount').textContent = selectedUrls.length + ' selected';
        }

        document.getElementById('mediaPickerSearch').addEventListener('input', function (e) {
            const q = e.target.value.toLowerCase();
            if (!mediaCache) return;
            const filtered = mediaCache.filter(function (img) { return img.name.toLowerCase().indexOf(q) > -1; });
            renderMediaGrid(filtered);
            // re-mark previously selected cells after re-render
            selectedUrls.forEach(function (url) {
                const cell = document.querySelector('#mediaPickerGrid .media-cell[data-url="' + CSS.escape(url) + '"]');
                if (cell) {
                    cell.classList.add('border-brand-600');
                    cell.querySelector('.check-badge').classList.remove('hidden');
                    cell.querySelector('.check-badge').classList.add('flex');
                }
            });
        });

        window.confirmMediaPicker = function () {
            if (pickerMode === 'hero') {
                document.getElementById('heroImagePicked').value = selectedUrls[0] || '';
                const preview = document.getElementById('heroImagePreview');
                preview.innerHTML = selectedUrls[0]
                    ? '<img src="..' + selectedUrls[0] + '" class="h-24 object-contain bg-slate-50 rounded-lg border p-1">'
                    : '';
            } else {
                const container = document.getElementById('galleryPickedInputs');
                const preview = document.getElementById('galleryImagesPreview');
                container.innerHTML = '';
                selectedUrls.forEach(function (url) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'gallery_images_picked[]';
                    input.value = url;
                    container.appendChild(input);

                    const img = document.createElement('img');
                    img.src = '..' + url;
                    img.className = 'h-16 w-16 object-cover rounded-lg border bg-white';
                    preview.appendChild(img);
                });
            }
            closeMediaPicker();
        };
    })();
    </script>

    <!-- Submit -->
    <div class="flex justify-end pb-6">
        <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white px-8 py-3 rounded-xl shadow font-bold text-base transition flex items-center gap-2">
            <i class="fas fa-rocket"></i> Save &amp; Deploy Page
        </button>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
