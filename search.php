<?php
include_once 'includes/header.php';

$jsonFile = 'products.json';
$products = [];
$searchResults = [];
$searchQuery = mb_substr(trim($_GET['q'] ?? ''), 0, 100); // Bug #3: cap length

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true) ?: [];
    $products = array_values(array_filter($products, fn($p) => empty($p['archived']))); // hide archived
}

if ($searchQuery !== '') {
    foreach ($products as $product) {
        $nameMatch = stripos($product['name'], $searchQuery) !== false;
        $descMatch = isset($product['description']) && stripos(strip_tags($product['description']), $searchQuery) !== false;
        
        if ($nameMatch || $descMatch) {
            $searchResults[] = $product;
        }
    }
}
?>

<main class="bg-cream-soft min-h-[70vh] py-8">
    <div class="container mx-auto px-4">
        
        <div class="mb-6 pb-3 border-b-2 border-gray-200">
            <h1 class="font-heading text-2xl font-bold text-gray-800">
                Search Results for: <span class="text-[#DB1456]">"<?= htmlspecialchars($searchQuery) ?>"</span>
            </h1>
            <p class="text-gray-500 text-sm mt-1">Found <?= count($searchResults) ?> item(s)</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            
            <?php if (!empty($searchResults)): ?>
                <?php foreach ($searchResults as $product): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 group flex flex-col transform hover:-translate-y-1">
                        
                        <div class="relative w-full pt-[100%] overflow-hidden bg-gray-50">
                            <a href="product-details.php?id=<?= htmlspecialchars($product['id']) ?>" class="absolute inset-0 p-2">
                                <img src="<?= htmlspecialchars($product['image_url']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     loading="lazy"
                                     decoding="async"
                                     class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
                            </a>
                            
                            <?php if (!empty($product['old_price']) && (float)$product['old_price'] > 0 && $product['old_price'] > $product['price']): ?>
                                <?php $discount = round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>
                                <div class="absolute top-3 left-3 bg-[#DB1456] text-white text-xs font-bold px-2 py-1 rounded shadow-sm">
                                    -<?= $discount ?>%
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-4 flex flex-col flex-grow">
                            <!-- Dynamic Category Added Here -->
                            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1.5"><?= htmlspecialchars(categoryName($product['category'] ?? '', $navCategories)) ?></span>
                            
                            <a href="product-details.php?id=<?= htmlspecialchars($product['id']) ?>">
                                <h3 class="text-[14px] font-semibold text-gray-800 leading-snug line-clamp-2 hover:text-[#DB1456] transition-colors mb-3 min-h-[42px]">
                                    <?= htmlspecialchars($product['name']) ?>
                                </h3>
                            </a>
                            
                            <div class="mt-auto">
                                <div class="flex items-center gap-2 mb-3.5">
                                    <span class="text-[#DB1456] font-black text-lg">
                                        ৳ <?= number_format($product['price']) ?>
                                    </span>
                                    <?php if (isset($product['old_price'])): ?>
                                        <span class="text-gray-400 line-through text-sm font-medium">
                                            ৳ <?= number_format($product['old_price']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <button type="button" onclick="addToCart('<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>', 1, true)" class="w-full bg-[#DB1456] hover:bg-[#B80E43] text-white py-2.5 rounded-lg flex items-center justify-center gap-2 text-sm font-bold transition-all duration-300 transform hover:-translate-y-0.5 shadow-sm">
                                    <i class="fas fa-shopping-basket"></i> অর্ডার করুন
                                </button>
                            </div>
                        </div>
                        
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 bg-white rounded-xl border border-gray-100 text-center flex flex-col items-center justify-center shadow-sm">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                        <i class="fas fa-search text-4xl"></i>
                    </div>
                    <?php if (empty($searchQuery)): ?>
                        <h3 class="font-heading text-xl font-bold text-gray-800 mb-2">Start Searching</h3>
                        <p class="text-gray-500">Enter a product name or keyword in the search bar above.</p>
                    <?php else: ?>
                        <h3 class="font-heading text-xl font-bold text-gray-800 mb-2">No Products Found</h3>
                        <p class="text-gray-500">We couldn't find any products matching "<?= htmlspecialchars($searchQuery) ?>".</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<?php if ($searchQuery !== ''): ?>
<script>
    trackPixel('Search', {
        search_string: <?= json_encode($searchQuery) ?>,
        content_type: 'product',
        content_ids: <?= json_encode(array_map(function ($p) { return (string)$p['id']; }, $searchResults)) ?>
    });
</script>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>