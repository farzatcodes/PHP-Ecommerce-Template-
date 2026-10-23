<?php
include_once 'includes/header.php';

$jsonFile = 'products.json';
$product = null;
$products = [];

// Fetch data from JSON
if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true);
    if (is_array($products)) {
        $products = array_values(array_filter($products, fn($p) => empty($p['archived']))); // hide archived
    }

    if (isset($_GET['id'])) {
        $productId = $_GET['id'];
        foreach ($products as $p) {
            if ($p['id'] === $productId) {
                $product = $p;
                break;
            }
        }
    }
}

// Fallback if product not found
if (!$product) {
    $product = [
        "id" => "000",
        "name" => "Men's And Women's Shirts Fixed Non-Slip Shirt Stay Belt(Free Size)",
        "price" => "499",
        "old_price" => "790",
        "image_url" => "image_8cf6a1.png",
        "gallery" => [
            "image_8cf6a1.png",
            "image_8cf6a1.png",
            "image_8cf6a1.png"
        ],
        "description" => "<p>Description not found.</p>",
        "reviews" => []
    ];
} else {
    // Mock old price if missing
    if(!isset($product['old_price'])) {
        $product['old_price'] = (intval($product['price']) + 300);
    }
}
?>

<main class="bg-cream-soft min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-5xl"> 
        
        <!-- Main Product Card Container -->
        <div class="flex flex-col lg:flex-row gap-6 bg-white p-4 md:p-6 rounded-xl shadow-sm">
            
            <!-- Left Side: Images -->
            <div class="w-full lg:w-[48%] flex flex-col gap-3">
                
                <!-- Main Image Container -->
                <div class="relative bg-white border border-gray-100 rounded-lg overflow-hidden flex items-center justify-center p-2 group shadow-sm">
                    
                    <!-- Left Arrow -->
                    <button aria-label="Previous image" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white rounded-full flex items-center justify-center text-[#DB1456] shadow-md z-10 hover:bg-gray-50 transition-colors opacity-80 group-hover:opacity-100">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </button>

                    <!-- Main Image -->
                    <img src="<?= htmlspecialchars(productImageVariant($product['image_url'], 'detail')) ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         width="450" height="450"
                         fetchpriority="high"
                         loading="eager"
                         decoding="async"
                         class="w-full h-auto max-h-[450px] object-contain">

                    <!-- Right Arrow -->
                    <button aria-label="Next image" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 bg-white rounded-full flex items-center justify-center text-[#DB1456] shadow-md z-10 hover:bg-gray-50 transition-colors opacity-80 group-hover:opacity-100">
                        <i class="fas fa-arrow-right text-sm"></i>
                    </button>
                </div>

                <!-- Thumbnails Row -->
                <div class="flex gap-2 mt-1 flex-wrap">
                    <?php if (!empty($product['gallery']) && is_array($product['gallery'])): ?>
                        <?php foreach ($product['gallery'] as $index => $galleryImage): ?>
                            <div class="w-[70px] h-[70px] border <?= $index === 0 ? 'border-[#DB1456] border-2 shadow-sm' : 'border-gray-200 hover:border-[#DB1456]' ?> rounded overflow-hidden cursor-pointer p-0.5 transition-colors">
                                <img src="<?= htmlspecialchars(productImageVariant($galleryImage, 'thumb')) ?>" alt="Thumbnail <?= $index + 1 ?>" width="70" height="70" loading="lazy" decoding="async" class="w-full h-full object-contain <?= $index === 0 ? '' : 'opacity-70' ?>">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="w-[70px] h-[70px] border-2 border-[#DB1456] shadow-sm rounded overflow-hidden cursor-pointer p-0.5">
                            <img src="<?= htmlspecialchars(productImageVariant($product['image_url'], 'thumb')) ?>" alt="Thumbnail" width="70" height="70" loading="lazy" decoding="async" class="w-full h-full object-contain">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Side: Details & Actions -->
            <div class="w-full lg:w-[52%] flex flex-col">
                
                <!-- Title -->
                <h1 class="font-heading text-[#2B2B2B] font-semibold text-xl md:text-[22px] leading-snug mb-2">
                    <?= htmlspecialchars($product['name']) ?>
                </h1>
                
                <!-- Price -->
                <div class="flex items-center gap-3 mb-5">
                    <span class="text-[#DB1456] font-black text-2xl">
                        Tk <?= number_format((float)$product['price']) ?>
                    </span>
                    <?php if (isset($product['old_price'])): ?>
                        <span class="text-black line-through text-base font-medium opacity-60">
                            Tk <?= number_format((float)$product['old_price']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons Stack -->
                <div class="flex flex-col gap-3 mb-6">
                    <!-- Order Now Button (Adds to cart & redirects to checkout) -->
                    <button type="button" onclick="addToCart('<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>', 1, true)" class="w-full bg-[#DB1456] hover:bg-[#B80E43] text-white py-3.5 px-4 rounded-lg flex items-center justify-center gap-2 text-[17px] font-bold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 bengali-text cursor-pointer">
                        <i class="fas fa-shopping-basket"></i> অর্ডার করুন
                    </button>

                    <!-- Add to Cart Button (Adds to cart silently, stays on page) -->
                    <button type="button" onclick="addToCart('<?= htmlspecialchars($product['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($product['name'], ENT_QUOTES) ?>', <?= $product['price'] ?>, '<?= htmlspecialchars($product['image_url'], ENT_QUOTES) ?>', 1, false)" class="w-full bg-white border-2 border-[#111] hover:bg-[#111] text-[#111] hover:text-white py-3 px-4 rounded-lg flex items-center justify-center gap-2 text-[16px] font-bold transition-all duration-300 transform hover:-translate-y-0.5 bengali-text cursor-pointer">
                        <i class="fas fa-cart-plus"></i> কার্টে যোগ করুন
                    </button>
                    
                </div>

                <!-- Delivery Info Table -->
                <div class="bg-gray-50 border border-gray-200 rounded-lg overflow-hidden mb-6 shadow-sm">
                    <div class="bg-white px-4 py-3 border-b border-gray-200">
                        <h3 class="text-center text-gray-800 text-[15px] font-bold bengali-text">কুরিয়ার ডেলিভারি খরচ</h3>
                    </div>
                    <table class="w-full border-collapse">
                        <tbody>
                            <tr class="border-b border-gray-200">
                                <td class="p-3 text-gray-700 bengali-text text-sm w-1/2 bg-white">ঢাকায় ডেলিভারি খরচ</td>
                                <td class="p-3 text-gray-900 text-sm font-bold w-1/2 bg-white text-right">৳ 60.00</td>
                            </tr>
                            <tr>
                                <td class="p-3 text-gray-700 bengali-text text-sm bg-white">ঢাকার বাইরের ডেলিভারি খরচ</td>
                                <td class="p-3 text-gray-900 text-sm font-bold bg-white text-right">৳ 130.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bengali Instruction Text -->
                <div class="bg-blue-50/50 border border-blue-100 rounded-lg p-4 mb-6">
                    <div class="text-[13px] text-gray-800 leading-relaxed space-y-2 bengali-text font-medium">
                        <p class="flex items-start gap-2"><i class="fas fa-info-circle text-blue-500 mt-0.5 opacity-80"></i> <span>১) উল্লেখিত ডেলিভারি চার্জ ১(এক) কেজি পর্যন্ত ওজনের পন্যের জন্য। পন্যের ওজন বাড়লে ডেলিভারি চার্জ ও বাড়বে।</span></p>
                        <p class="flex items-start gap-2"><i class="fas fa-info-circle text-blue-500 mt-0.5 opacity-80"></i> <span>২) ছবি এবং বর্ণনার সাথে পন্যের মিল থাকা সত্যেও আপনি পন্য গ্রহন করতে না চাইলে কুরিয়ার চার্জ ১৫০ টাকা কুরিয়ার অফিসে প্রদান করে পন্য আমাদের ঠিকানায় রিটার্ন করবেন।</span></p>
                        <p class="flex items-start gap-2"><i class="fas fa-info-circle text-blue-500 mt-0.5 opacity-80"></i> <span>৩) পন্য সংক্রান্ত যেকোনো তথ্যের জন্য আমাদের দেওয়া হোয়াটসঅ্যাপ নাম্বারে যোগাযোগ করুন।</span></p>
                    </div>
                </div>

                <!-- Meta Tags -->
                <div class="flex flex-col gap-2 text-[11px] font-medium text-black uppercase">
                    <div>PRODUCT CODE: <span class="font-bold text-gray-700"><?= htmlspecialchars($product['id']) ?></span></div>
                    <div class="flex items-center gap-1.5">
                        <span class="normal-case text-gray-600">Tags:</span> 
                        <span class="bg-[#2B2B2B] text-white px-2 py-0.5 rounded text-[10px] normal-case font-bold tracking-wide shadow-sm">WellCareBD</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Dynamic Product Tabs (Description, Policy, Reviews) -->
        <?php include_once 'includes/product-tabs.php'; ?>

        <!-- Related Products Section -->
        <div class="mt-8 mb-4">
            <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-200">
                <h2 class="font-heading text-xl md:text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-boxes text-[#DB1456]"></i> 
                    Related Products
                </h2>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
                <?php 
                // Display up to 5 related products (excluding current one)
                if (!empty($products)) {
                    $count = 0;
                    foreach ($products as $relatedProduct) {
                        // Skip current product
                        if (isset($productId) && $relatedProduct['id'] === $productId) {
                            continue;
                        }
                        if ($count >= 5) break;
                        ?>
                        
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 group flex flex-col transform hover:-translate-y-1">
                            <div class="relative w-full pt-[100%] overflow-hidden bg-gray-50">
                                <a href="product-details.php?id=<?= htmlspecialchars($relatedProduct['id']) ?>" class="absolute inset-0 p-2">
                                    <img src="<?= htmlspecialchars(productImageVariant($relatedProduct['image_url'], 'grid')) ?>"
                                         alt="<?= htmlspecialchars($relatedProduct['name']) ?>"
                                         width="340" height="340"
                                         loading="lazy"
                                         decoding="async"
                                         class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
                                </a>

                                <div class="absolute top-3 right-3 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 translate-x-4 group-hover:translate-x-0">
                                    <button aria-label="Add to wishlist" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-gray-600 hover:text-white hover:bg-[#DB1456] shadow-md transition-colors">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="p-4 flex flex-col flex-grow">
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-1.5"><?= htmlspecialchars(categoryName($relatedProduct['category'] ?? '', $navCategories)) ?></span>
                                <a href="product-details.php?id=<?= htmlspecialchars($relatedProduct['id']) ?>">
                                    <h3 class="text-[14px] font-semibold text-gray-800 leading-snug line-clamp-2 hover:text-[#DB1456] transition-colors mb-3 min-h-[42px]">
                                        <?= htmlspecialchars($relatedProduct['name']) ?>
                                    </h3>
                                </a>
                                
                                <div class="mt-auto">
                                    <div class="flex items-end gap-2 mb-3">
                                        <span class="text-[#DB1456] font-black text-lg leading-none">
                                            ৳ <?= number_format($relatedProduct['price']) ?>
                                        </span>
                                    </div>
                                    <button type="button" onclick="addToCart('<?= htmlspecialchars($relatedProduct['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($relatedProduct['name'], ENT_QUOTES) ?>', <?= $relatedProduct['price'] ?>, '<?= htmlspecialchars($relatedProduct['image_url'], ENT_QUOTES) ?>', 1, true)" class="w-full bg-[#DB1456] hover:bg-[#B80E43] text-white py-2.5 rounded-lg flex items-center justify-center gap-2 text-sm font-bold transition-all duration-300">
                                        <i class="fas fa-shopping-basket"></i> অর্ডার করুন
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php
                        $count++;
                    }
                }
                ?>
            </div>
        </div>

    </div>
</main>

<script>
    trackPixel('ViewContent', {
        content_ids: [<?= json_encode((string)$product['id']) ?>],
        content_name: <?= json_encode($product['name']) ?>,
        content_type: 'product',
        value: <?= (float)$product['price'] ?>,
        currency: 'BDT'
    });
</script>

<?php
include_once 'includes/footer.php';
?>