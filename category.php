<?php
require_once __DIR__ . '/includes/categories.php';

$rawSlug = isset($_GET['slug']) ? $_GET['slug'] : '';
$slug = htmlspecialchars($rawSlug);

// Map slugs to display names — sourced from categories.json
$categories = loadCategories();
$categoryMap = [];
foreach ($categories as $c) {
    $categoryMap[$c['slug']] = $c['name'];
}

$displayTitle = isset($categoryMap[$slug]) ? $categoryMap[$slug] : 'Category Products';

// Load products
$jsonFile = 'products.json';
$allProducts = [];
if (file_exists($jsonFile)) {
    $jsonString = file_get_contents($jsonFile);
    $decoded = json_decode($jsonString, true);
    if (is_array($decoded)) {
        $allProducts = array_values(array_filter($decoded, fn($p) => empty($p['archived']))); // hide archived
    }
}

// Filter products by the chosen category slug
$categoryProducts = array_filter($allProducts, function($product) use ($slug) {
    return isset($product['category']) && $product['category'] === $slug;
});

include 'includes/header.php';
?>

<!-- Main Content Area -->
<main class="flex-grow max-w-7xl mx-auto px-4 py-8 w-full">
    
    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mb-4 text-sm text-gray-500 flex items-center gap-2">
        <a href="/" class="hover:text-[#DB1456] transition-colors">Home</a>
        <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
        <span class="text-gray-700 font-medium"><?php echo $displayTitle; ?></span>
    </nav>

    <div class="mb-6 flex items-center justify-between border-b-2 border-gray-200 pb-4">
        <h1 class="font-heading text-2xl font-bold text-gray-800"><?php echo $displayTitle; ?></h1>
        <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full"><?php echo count($categoryProducts); ?> Items</span>
    </div>

    <?php if (count($categoryProducts) > 0): ?>
        <!-- Product Grid -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            <?php foreach ($categoryProducts as $product): ?>
                <?php
                    $id = htmlspecialchars($product['id'] ?? '');
                    $name = htmlspecialchars($product['name'] ?? 'Unknown Product');
                    $price = htmlspecialchars($product['price'] ?? '0');
                    $old_price = htmlspecialchars($product['old_price'] ?? '');
                    $image_url = htmlspecialchars(productImageVariant($product['image_url'] ?? '', 'grid') ?: 'https://via.placeholder.com/300?text=No+Image');
                    
                    // Calculate discount percentage safely
                    $discount_percentage = 0;
                    if (!empty($old_price) && is_numeric($old_price) && is_numeric($price) && $old_price > $price) {
                        $discount_percentage = round((($old_price - $price) / $old_price) * 100);
                    }
                ?>
                <!-- Single Product Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 group flex flex-col transform hover:-translate-y-1">
                    <div class="relative w-full pt-[100%] overflow-hidden bg-gray-50">
                        <a href="product-details.php?id=<?php echo $id; ?>" class="absolute inset-0 p-2">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo $name; ?>" width="340" height="340" loading="lazy" decoding="async" class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
                        </a>
                        <?php if ($discount_percentage > 0): ?>
                            <div class="absolute top-3 left-3 bg-[#DB1456] text-white text-xs font-bold px-2 py-1 rounded shadow-sm">
                                -<?php echo $discount_percentage; ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 flex flex-col flex-grow">
                        <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1.5"><?php echo htmlspecialchars(categoryName($product['category'] ?? '', $navCategories)); ?></span>
                        <a href="product-details.php?id=<?php echo $id; ?>">
                            <h3 class="text-[14px] font-semibold text-gray-800 leading-snug line-clamp-2 hover:text-[#DB1456] transition-colors mb-3 min-h-[42px]">
                                <?php echo $name; ?>
                            </h3>
                        </a>
                        <div class="mt-auto">
                            <div class="flex items-center gap-2 mb-3.5">
                                <span class="text-[#DB1456] font-black text-lg">৳ <?php echo $price; ?></span>
                                <?php if (!empty($old_price)): ?>
                                    <span class="text-gray-400 text-sm line-through decoration-gray-400">৳ <?php echo $old_price; ?></span>
                                <?php endif; ?>
                            </div>
                            <button onclick="addToCart('<?php echo htmlspecialchars($id, ENT_QUOTES); ?>', '<?php echo addslashes($name); ?>', <?php echo $price; ?>, '<?php echo htmlspecialchars($image_url, ENT_QUOTES); ?>', 1, true)" class="w-full bg-[#DB1456] hover:bg-[#B80E43] text-white py-2.5 rounded-lg flex items-center justify-center gap-2 text-sm font-bold transition-all duration-300 transform hover:-translate-y-0.5 shadow-sm">
                                <i class="fas fa-shopping-basket"></i> অর্ডার করুন
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4 mx-auto">
                <i class="fas fa-box-open text-4xl"></i>
            </div>
            <h2 class="font-heading text-xl font-bold text-gray-700 mb-2">No Products Found</h2>
            <p class="text-gray-500 mb-6">We couldn't find any products in the "<?php echo $displayTitle; ?>" category right now.</p>
            <a href="index.php" class="inline-block bg-[#DB1456] text-white px-6 py-2.5 rounded-full font-medium hover:bg-[#B80E43] transition-colors">Return to Home</a>
        </div>
    <?php endif; ?>

</main>

<?php include 'includes/footer.php'; ?>