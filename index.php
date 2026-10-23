<?php
include_once 'includes/header.php';

$jsonFile = 'products.json';
$products = [];

if (file_exists($jsonFile)) {
    $jsonData = file_get_contents($jsonFile);
    $products = json_decode($jsonData, true) ?: [];
    $products = array_values(array_filter($products, fn($p) => empty($p['archived']))); // hide archived
}
?>

<main class="bg-cream-soft min-h-screen pb-12">

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-ink">
        <div class="absolute inset-0 bg-gradient-to-br from-brand-600/30 via-ink to-ink"></div>
        <div class="absolute -right-24 -top-24 w-96 h-96 bg-brand-500/25 rounded-full blur-3xl"></div>
        <div class="absolute -left-24 bottom-0 w-72 h-72 bg-peach/15 rounded-full blur-3xl"></div>

        <div class="relative container mx-auto px-4 py-14 md:py-20 text-center">
            <span class="inline-block bg-white/10 text-white/90 text-xs font-bold tracking-widest uppercase px-4 py-1.5 rounded-full mb-5 border border-white/10">
                Trusted Online Shop in Bangladesh
            </span>
            <h1 class="font-heading text-3xl sm:text-4xl md:text-5xl font-extrabold text-cream leading-tight max-w-3xl mx-auto">
                Everything You Need, <span class="bg-gradient-to-r from-peach to-brand-500 bg-clip-text text-transparent">Delivered Fast.</span>
            </h1>
            <p class="text-white/70 text-base md:text-lg mt-4 max-w-xl mx-auto">
                Quality products at honest prices — with nationwide cash on delivery.
            </p>
            <a href="#products" class="inline-flex items-center gap-2 mt-8 bg-brand-600 hover:bg-brand-700 text-white font-bold px-8 py-3.5 rounded-lg shadow-lg shadow-brand-600/30 transition-all duration-300 transform hover:-translate-y-0.5">
                Shop Now <i class="fas fa-arrow-right text-sm"></i>
            </a>

            <!-- Trust badges -->
            <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-3 max-w-3xl mx-auto text-left">
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <i class="fas fa-shield-alt text-brand-400 text-lg"></i>
                    <span class="text-white/90 text-sm font-semibold">Quality You Can Trust</span>
                </div>
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <i class="fas fa-truck text-brand-400 text-lg"></i>
                    <span class="text-white/90 text-sm font-semibold">Fast &amp; Safe Delivery</span>
                </div>
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <i class="fas fa-money-bill-wave text-brand-400 text-lg"></i>
                    <span class="text-white/90 text-sm font-semibold">Cash on Delivery</span>
                </div>
                <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-lg px-4 py-3">
                    <i class="fas fa-award text-brand-400 text-lg"></i>
                    <span class="text-white/90 text-sm font-semibold">Trusted Service</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <div id="products" class="container mx-auto px-4 py-8">
        
        <div class="flex items-center justify-between mb-6 pb-3 border-b-2 border-gray-200">
            <h2 class="font-heading text-2xl font-bold text-gray-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded bg-[#DB1456]/15 flex items-center justify-center text-[#DB1456]">
                    <i class="fas fa-box-open"></i>
                </div>
                Trending Products
            </h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            
            <?php if (!empty($products)): ?>
                <?php $productIndex = 0; foreach ($products as $product): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300 group flex flex-col transform hover:-translate-y-1">

                        <div class="relative w-full pt-[100%] overflow-hidden bg-gray-50">
                            <a href="product-details.php?id=<?= htmlspecialchars($product['id']) ?>" class="absolute inset-0 p-2">
                                <img src="<?= htmlspecialchars(productImageVariant($product['image_url'], 'grid')) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     width="340" height="340"
                                     loading="<?= $productIndex < 5 ? 'eager' : 'lazy' ?>"
                                     <?= $productIndex < 5 ? 'fetchpriority="high"' : '' ?>
                                     decoding="async"
                                     class="w-full h-full object-contain mix-blend-multiply group-hover:scale-105 transition-transform duration-500">
                            </a>
                            
                            <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
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
                                    <?php if (!empty($product['old_price'])): ?>
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
                <?php $productIndex++; endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 bg-white rounded-xl border border-gray-100 text-center flex flex-col items-center justify-center shadow-sm">
                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center text-gray-300 mb-4">
                        <i class="fas fa-box-open text-4xl"></i>
                    </div>
                    <h3 class="font-heading text-xl font-bold text-gray-800 mb-2">No Products Found</h3>
                    <p class="text-gray-500">Add products from the admin panel to see them here.</p>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>