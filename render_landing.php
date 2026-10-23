<?php
// This file is called from within /landing/{slug}/index.php
if (!isset($landing_slug)) {
    die("Invalid landing page configuration.");
}

// 1. Fetch Config
$pagesFile = __DIR__ . '/landing_pages.json';
$landingPages = file_exists($pagesFile) ? json_decode(file_get_contents($pagesFile), true) ?? [] : [];

$pageConfig = null;
foreach ($landingPages as $page) {
    if ($page['slug'] === $landing_slug) {
        $pageConfig = $page;
        break;
    }
}
if (!$pageConfig) die("Landing page not found.");

// 2. Fetch Product
$productsFile = __DIR__ . '/products.json';
$products = file_exists($productsFile) ? json_decode(file_get_contents($productsFile), true) ?? [] : [];

$linkedProduct = null;
foreach ($products as $prod) {
    if ($prod['id'] === $pageConfig['product_id']) {
        $linkedProduct = $prod;
        break;
    }
}

$themeColor = htmlspecialchars($pageConfig['theme_color'] ?? '#DB1456');
$phone = htmlspecialchars($pageConfig['contact_phone'] ?? '');

// 3. Load global settings (pixel codes)
$settingsFile = __DIR__ . '/settings.json';
$siteSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?? [] : [];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageConfig['page_title']); ?> - WellCareBD</title>
    
    <!-- Compiled Tailwind CSS (was the cdn.tailwindcss.com Play CDN — dropped because it
         depends on reaching an external script at runtime: Cloudflare's Rocket Loader once
         mangled it (see git history), and any visitor whose network/ad-blocker blocks the
         CDN gets an unstyled page. This file is built locally from tailwind.config.js /
         input.css via the existing `npm run build` — same classes, no runtime dependency. -->
    <link rel="stylesheet" href="/assets/css/output.css">

    <!-- Local Font Awesome -->
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">

    <?php if (!empty($siteSettings['pixel_head'])): ?>
    <!-- @head pixel -->
    <?php echo preg_replace('/<script(?![^>]*data-cfasync)(\s|>)/i', '<script data-cfasync="false"$1', $siteSettings['pixel_head']); ?>
    <?php endif; ?>

    <style>
        /* Compiled CSS's default sans stack (tailwind.config.js) is Arial-only; this page's
           text is mostly Bengali, so keep the Bengali web fonts as the default here, same
           as the old inline tailwind.config fontFamily.sans override did. */
        body { background-color: #f3f4f6; font-family: 'Hind Siliguri', 'Kalpurush', 'SolaimanLipi', Arial, sans-serif; }
        .theme-bg { background-color: <?php echo $themeColor; ?>; }
        .theme-text { color: <?php echo $themeColor; ?>; }
        .theme-border { border-color: <?php echo $themeColor; ?>; }
        .theme-hover:hover { filter: brightness(90%); }
        .checkout-input:focus { border-color: <?php echo $themeColor; ?>; outline: none; box-shadow: 0 0 0 2px <?php echo $themeColor; ?>33; }
        .pkg-selected { border-color: <?php echo $themeColor; ?>; background-color: <?php echo $themeColor; ?>0d; }
    </style>
    <meta name="theme-color" content="#0A0A0A">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/branding/favicon-32.png">
    <link rel="apple-touch-icon" href="/assets/branding/apple-touch-icon.png">
</head>
<body class="text-gray-800 pb-12">

    <?php if (!empty($siteSettings['pixel_body'])): ?>
    <!-- @body pixel -->
    <?php echo $siteSettings['pixel_body']; ?>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto bg-white shadow-2xl min-h-screen overflow-hidden">
        
        <!-- Animated Ticker Header -->
        <?php if(!empty($pageConfig['ticker_text'])): ?>
        <div class="bg-brand-50 text-brand-700 font-bold py-2 px-4 overflow-hidden text-center text-sm md:text-base border-b border-brand-100">
            <span class="inline-block animate-pulse"><?php echo htmlspecialchars($pageConfig['ticker_text']); ?></span>
        </div>
        <?php endif; ?>

        <!-- Logo (black bar: the logo is cream/pink) -->
        <div class="py-4 text-center bg-black border-b border-white/10">
            <a href="/" class="inline-block" aria-label="WellCareBD home">
                <img src="/assets/branding/logo-wordmark.png" alt="WellCareBD" width="560" height="112" class="h-9 md:h-10 w-auto mx-auto">
            </a>
        </div>

        <!-- Hero Section -->
        <div class="p-4 md:p-8">
            
            <?php if(!empty($pageConfig['top_subheading'])): ?>
                <h3 class="text-center text-xl md:text-2xl font-bold text-gray-800 mb-3"><?php echo htmlspecialchars($pageConfig['top_subheading']); ?></h3>
            <?php endif; ?>

            <h1 class="text-center text-2xl md:text-4xl font-extrabold text-[#111] leading-snug mb-4">
                <?php echo nl2br(htmlspecialchars($pageConfig['hero_heading'])); ?>
            </h1>

            <?php if(!empty($pageConfig['hero_subheading'])): ?>
                <p class="text-center text-lg font-bold text-gray-700 mb-6">
                    <?php echo nl2br(htmlspecialchars($pageConfig['hero_subheading'])); ?>
                </p>
            <?php endif; ?>

            <!-- Hero Image -->
            <?php $displayImage = !empty($pageConfig['hero_image']) ? $pageConfig['hero_image'] : ($linkedProduct['image_url'] ?? ''); ?>
            <?php if($displayImage): ?>
                <img src="<?php echo htmlspecialchars($displayImage); ?>" alt="Offer Image" class="w-full max-w-lg mx-auto rounded-lg shadow-md mb-8 object-contain bg-gray-50 border border-gray-100">
            <?php endif; ?>

            <!-- CTA Buttons -->
            <div class="flex flex-col sm:flex-row justify-center items-center gap-4 mb-8">
                <button onclick="scrollToCheckout()" class="w-full sm:w-auto theme-bg theme-hover text-white font-bold text-xl px-8 py-3.5 rounded shadow-lg transform transition-transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-shopping-cart"></i> এখনই অর্ডার করুন
                </button>
                <?php if ($phone !== ''): ?>
                <a href="tel:<?php echo $phone; ?>" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white font-bold text-xl px-8 py-3.5 rounded shadow-lg transform transition-transform hover:scale-105 flex items-center justify-center gap-2">
                    <i class="fas fa-phone-alt"></i> Call: <?php echo $phone; ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Highlight Offer Box -->
            <?php if(!empty($pageConfig['offer_text'])): ?>
            <?php $hasCountdown = !empty($pageConfig['countdown_date']); ?>
            <div class="bg-brand-50 border border-red-200 rounded-lg p-5 text-center mb-10 shadow-sm">
                <h3 class="text-red-600 font-bold text-xl md:text-2xl leading-snug">
                    <?php echo htmlspecialchars($pageConfig['offer_text']); ?>
                </h3>
                <?php if($hasCountdown): ?>
                <div class="mt-4 flex justify-center gap-3 text-center" id="countdown-box">
                    <div class="bg-white px-3 py-2 rounded border border-brand-100 shadow-sm min-w-[52px]"><span class="block text-2xl font-black theme-text" id="cd-days">00</span><span class="text-xs font-bold text-gray-500">Days</span></div>
                    <div class="bg-white px-3 py-2 rounded border border-brand-100 shadow-sm min-w-[52px]"><span class="block text-2xl font-black theme-text" id="cd-hours">00</span><span class="text-xs font-bold text-gray-500">Hours</span></div>
                    <div class="bg-white px-3 py-2 rounded border border-brand-100 shadow-sm min-w-[52px]"><span class="block text-2xl font-black theme-text" id="cd-mins">00</span><span class="text-xs font-bold text-gray-500">Mins</span></div>
                    <div class="bg-white px-3 py-2 rounded border border-brand-100 shadow-sm min-w-[52px]"><span class="block text-2xl font-black theme-text" id="cd-secs">00</span><span class="text-xs font-bold text-gray-500">Secs</span></div>
                </div>
                <?php endif; ?>
                <button onclick="scrollToCheckout()" class="mt-5 theme-bg text-white font-bold px-6 py-2 rounded shadow theme-hover text-sm">
                    <i class="fas fa-shopping-cart"></i> অর্ডার করতে চাই
                </button>
            </div>
            <?php endif; ?>

            <!-- Features Section -->
            <?php if(!empty($pageConfig['features']) && count($pageConfig['features']) > 0 && $pageConfig['features'][0] !== ""): ?>
            <div class="mb-10">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span>📌</span> ব্যবহারের সুবিধা সমুহ-
                </h2>
                <div class="w-full h-1 bg-gray-100 mb-6 relative"><div class="absolute left-0 top-0 h-1 w-16 theme-bg"></div></div>
                
                <div class="space-y-4">
                    <?php foreach($pageConfig['features'] as $feature): if(trim($feature) === '') continue; ?>
                    <div class="flex items-start gap-3 bg-gray-50 p-4 rounded-lg border border-gray-100 shadow-sm">
                        <span class="text-green-500 text-xl mt-0.5"><i class="fas fa-check-circle"></i></span>
                        <span class="font-bold text-lg text-gray-800 leading-snug"><?php echo htmlspecialchars(trim($feature)); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-6">
                    <button onclick="scrollToCheckout()" class="theme-bg text-white font-bold px-8 py-3 rounded shadow-md hover:bg-brand-700">
                        <i class="fas fa-shopping-cart"></i> অর্ডার করতে চাই
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gallery / Review Screenshots Carousel -->
            <?php $galleryImages = $pageConfig['gallery_images'] ?? []; ?>
            <?php if(!empty($galleryImages)): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <span>📸</span> রিভিউ ও প্রোডাক্টের ছবি-
                </h2>
                <div class="w-full h-1 bg-gray-100 mb-6 relative"><div class="absolute left-0 top-0 h-1 w-16 theme-bg"></div></div>

                <?php if(count($galleryImages) === 1): ?>
                    <img src="<?php echo htmlspecialchars($galleryImages[0]); ?>" class="w-full rounded-lg border border-gray-200 object-contain bg-gray-50 max-h-80">
                <?php else: ?>
                <!-- Carousel -->
                <div class="relative rounded-lg overflow-hidden border border-gray-200 bg-gray-50 shadow-sm" id="lp-carousel">
                    <div id="lp-track" class="flex transition-transform duration-500 ease-in-out">
                        <?php foreach($galleryImages as $img): ?>
                        <div class="min-w-full">
                            <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-72 md:h-96 object-contain bg-gray-50">
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Prev / Next -->
                    <button onclick="lpSlide(-1)" class="absolute left-2 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full w-9 h-9 flex items-center justify-center text-lg transition">&#8249;</button>
                    <button onclick="lpSlide(1)"  class="absolute right-2 top-1/2 -translate-y-1/2 bg-black/40 hover:bg-black/60 text-white rounded-full w-9 h-9 flex items-center justify-center text-lg transition">&#8250;</button>

                    <!-- Dot counter -->
                    <div class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5" id="lp-dots"></div>
                </div>
                <?php endif; ?>

                <div class="text-center mt-6">
                    <button onclick="scrollToCheckout()" class="theme-bg theme-hover text-white font-bold px-8 py-3 rounded shadow-md">
                        <i class="fas fa-shopping-cart"></i> অর্ডার করতে চাই
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Checkout Form Section -->
            <div id="checkout-section" class="bg-white rounded-xl border border-gray-200 shadow-xl overflow-hidden mt-8">
                <div class="bg-gray-50 p-6 border-b border-gray-200 text-center">
                    <h2 class="text-2xl font-black text-gray-900">এখানে অর্ডার করুন!</h2>
                    <p class="text-sm font-bold mt-2 text-gray-600">
                        সঠিক ভাবে পণ্য অর্ডার করতে, অনুগ্রহ করে আপনার সম্পূর্ণ নাম, মোবাইল নম্বর, সম্পূর্ণ ঠিকানা লিখুন, এবং <span class="theme-text">"অর্ডার কনফার্ম করুন"</span> বাটনে ক্লিক করুন।
                    </p>
                </div>

                <form action="/process_checkout.php" method="POST" class="p-6 md:p-8" id="landing-checkout-form">
                    
                    <input type="hidden" name="cart_data" id="landing_cart_data">
                    <input type="hidden" name="payment_method" value="cod">

                    <?php
                    $packages = $pageConfig['packages'] ?? [];
                    $hasPackages = !empty($packages);
                    ?>

                    <!-- Package / Product selector -->
                    <div class="mb-8">
                        <h3 class="font-bold text-gray-800 mb-3 border-b pb-2">যেকোনো একটি প্যাকেজ নির্বাচন করুন-</h3>

                        <?php if($hasPackages): ?>
                        <div class="space-y-3" id="pkg-list">
                            <?php foreach($packages as $i => $pkg):
                                $isFirst = ($i === 0);
                            ?>
                            <label class="pkg-card flex items-center gap-4 p-4 border-2 rounded-xl cursor-pointer transition-all select-none
                                          <?php echo $isFirst ? 'pkg-selected' : 'border-gray-200 bg-white'; ?>"
                                   onclick="selectPkg(<?= $i ?>)">
                                <input type="radio" name="pkg_select" value="<?= $i ?>"
                                       <?php echo $isFirst ? 'checked' : ''; ?>
                                       class="sr-only">

                                <!-- Custom radio dot -->
                                <div class="pkg-dot w-6 h-6 rounded-full border-2 flex-shrink-0 flex items-center justify-center transition-all
                                            <?php echo $isFirst ? 'theme-bg border-transparent' : 'border-gray-300 bg-white'; ?>">
                                    <div class="w-2 h-2 rounded-full bg-white <?php echo $isFirst ? '' : 'opacity-0'; ?>"></div>
                                </div>

                                <img src="<?php echo htmlspecialchars($displayImage); ?>"
                                     class="w-16 h-16 object-cover rounded-lg border border-gray-100 bg-gray-50 flex-shrink-0">

                                <div class="flex-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($pkg['label']); ?></span>
                                        <?php if(!empty($pkg['badge'])): ?>
                                        <span class="text-[10px] font-black theme-bg text-white px-2 py-0.5 rounded-full leading-none">
                                            <?php echo htmlspecialchars($pkg['badge']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if((int)$pkg['quantity'] > 1): ?>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        প্রতি পিস ৳ <?php echo number_format((int)$pkg['price'] / (int)$pkg['quantity']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <span class="text-xl font-black theme-text">৳ <?php echo number_format((int)$pkg['price']); ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <?php else: ?>
                        <!-- Fallback: no packages — show single product -->
                        <div class="flex items-center gap-4 bg-white p-3 rounded-xl shadow-sm border border-red-200 relative overflow-hidden">
                            <?php if(!empty($linkedProduct['old_price']) && (int)$linkedProduct['old_price'] > (int)$linkedProduct['price']): ?>
                                <div class="absolute top-0 right-0 bg-brand-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl">
                                    সেভ ৳ <?php echo ((int)$linkedProduct['old_price'] - (int)$linkedProduct['price']); ?>
                                </div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($displayImage); ?>" class="w-20 h-20 object-cover rounded border border-gray-100 bg-gray-50">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($linkedProduct['name']); ?></h4>
                                <div class="mt-1 flex items-end gap-2">
                                    <span class="text-lg font-black theme-text">৳ <?php echo $linkedProduct['price']; ?></span>
                                    <?php if(!empty($linkedProduct['old_price'])): ?>
                                        <span class="text-xs text-gray-400 line-through font-bold">৳ <?php echo $linkedProduct['old_price']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-4 mb-8">
                        <h3 class="font-bold text-gray-800 border-b pb-2">Billing details</h3>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                            <input type="text" name="billing_name" required placeholder="নাম লিখুন" class="checkout-input w-full p-3 border border-gray-300 rounded bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">ফোন নাম্বার <span class="text-red-500">*</span></label>
                            <input type="tel" name="billing_phone" required placeholder="+88" class="checkout-input w-full p-3 border border-gray-300 rounded bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">ডেলিভারি ঠিকানা <span class="text-red-500">*</span></label>
                            <input type="text" name="billing_address" required placeholder="গ্রাম, থানা, জেলাসহ সম্পূর্ণ ঠিকানা" class="checkout-input w-full p-3 border border-gray-300 rounded bg-gray-50">
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-bold text-gray-800 border-b pb-2 mb-3">ডেলিভারি এরিয়া সিলেক্ট করুন</h3>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="delivery_zone" value="outside_dhaka" checked class="w-5 h-5 text-red-600 focus:ring-red-500" onchange="updateTotal()">
                                <span class="ml-3 font-bold text-gray-700">ঢাকার বাইরের: <span class="text-gray-900 font-black">৳ 120.00</span></span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 rounded cursor-pointer hover:bg-gray-50 transition-colors">
                                <input type="radio" name="delivery_zone" value="inside_dhaka" class="w-5 h-5 text-red-600 focus:ring-red-500" onchange="updateTotal()">
                                <span class="ml-3 font-bold text-gray-700">ঢাকার ভিতরে: <span class="text-gray-900 font-black">৳ 60.00</span></span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-5 rounded border border-gray-200 mb-6">
                        <div class="flex justify-between items-center mb-2 font-bold text-gray-600">
                            <span>Subtotal</span>
                            <span id="display_subtotal">৳ 0.00</span>
                        </div>
                        <div class="flex justify-between items-center mb-4 font-bold text-gray-600">
                            <span>Shipment</span>
                            <span id="display_shipping">৳ 120.00</span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t border-gray-300 font-black text-xl text-gray-900">
                            <span>Total</span>
                            <span class="theme-text" id="display_total">৳ 0.00</span>
                        </div>
                    </div>

                    <div class="bg-gray-100 p-4 rounded border border-gray-300 mb-6">
                        <div class="flex items-center gap-2 font-bold text-gray-800 mb-1">
                            <input type="radio" checked disabled class="w-4 h-4 text-gray-600">
                            <span>ক্যাশ অন ডেলিভেরি</span>
                        </div>
                        <p class="text-sm text-gray-600 ml-6">আমি অবশ্যই পণ্যটি রিসিভ করবো, ইনশাআল্লাহ!</p>
                    </div>

                    <button type="submit" class="w-full theme-bg theme-hover text-white font-black text-lg py-4 rounded shadow-lg flex justify-center items-center gap-2 transform transition-transform active:scale-[0.98]">
                        <i class="fas fa-lock"></i> অর্ডার কনফার্ম করুন <span id="btn_total"></span>
                    </button>

                </form>
            </div>

            <!-- Footer Links -->
            <div class="mt-12 pt-8 border-t border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <ul class="space-y-3 font-bold text-gray-600">
                            <?php if ($phone !== ''): ?>
                            <li><a href="tel:<?php echo $phone; ?>" class="flex items-center gap-3 hover:text-[#DB1456] transition"><i class="fas fa-phone-alt text-gray-400"></i> <?php echo $phone; ?></a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div>
                        <ul class="space-y-3 font-bold text-gray-600">
                            <li><a href="/" class="flex items-center gap-3 hover:text-[#DB1456] transition"><i class="fas fa-home text-gray-400"></i> Home Page</a></li>
                            <li><a href="/" class="flex items-center gap-3 hover:text-[#DB1456] transition"><i class="fas fa-link text-gray-400"></i> Refund & Returns Policy</a></li>
                            <li><a href="#" class="flex items-center gap-3 hover:text-[#DB1456] transition"><i class="fab fa-facebook-square text-gray-400"></i> Facebook Page</a></li>
                        </ul>
                    </div>
                </div>
                <div class="text-center font-bold text-sm text-gray-500">
                    &copy; <?php echo date('Y'); ?> WellCareBD. All Rights Reserved By <span class="theme-text">WellCareBD.</span>
                </div>
            </div>

        </div>
    </div>

    <script>
        /* ── Product defaults (fallback when no packages) ── */
        const productId    = "<?php echo $linkedProduct['id']; ?>";
        const productName  = "<?php echo addslashes($linkedProduct['name']); ?>";
        const productPrice = <?php echo (int)$linkedProduct['price']; ?>;
        const productImage = "<?php echo addslashes($displayImage); ?>";

        /* ── Packages ── */
        const packages = <?php echo json_encode(array_values($packages ?? [])); ?>;
        let selectedPkgIdx = 0;

        function selectPkg(idx) {
            selectedPkgIdx = idx;

            document.querySelectorAll('.pkg-card').forEach((card, i) => {
                const dot     = card.querySelector('.pkg-dot');
                const dotInner = dot.querySelector('div');
                if (i === idx) {
                    card.classList.add('pkg-selected');
                    card.classList.remove('border-gray-200', 'bg-white');
                    dot.classList.add('theme-bg', 'border-transparent');
                    dot.classList.remove('border-gray-300', 'bg-white');
                    dotInner.classList.remove('opacity-0');
                } else {
                    card.classList.remove('pkg-selected');
                    card.classList.add('border-gray-200', 'bg-white');
                    dot.classList.remove('theme-bg', 'border-transparent');
                    dot.classList.add('border-gray-300', 'bg-white');
                    dotInner.classList.add('opacity-0');
                }
                card.querySelector('input[type=radio]').checked = (i === idx);
            });

            updateTotal();
        }

        function getSubtotal() {
            if (packages.length > 0) return packages[selectedPkgIdx].price;
            return productPrice;
        }

        function buildCartData() {
            if (packages.length > 0) {
                const pkg = packages[selectedPkgIdx];
                return [{
                    id:    productId,
                    name:  productName + (pkg.label ? ' (' + pkg.label + ')' : ''),
                    price: pkg.price,
                    image: productImage,
                    qty:   pkg.quantity
                }];
            }
            return [{ id: productId, name: productName, price: productPrice, image: productImage, qty: 1 }];
        }

        function updateTotal() {
            let shippingCost = 120;
            for (const radio of document.getElementsByName('delivery_zone')) {
                if (radio.checked) { shippingCost = radio.value === 'inside_dhaka' ? 60 : 120; break; }
            }
            const subtotal = getSubtotal();
            const total    = subtotal + shippingCost;

            document.getElementById('display_subtotal').innerText = "৳ " + subtotal.toLocaleString('en-IN');
            document.getElementById('display_shipping').innerText = "৳ " + shippingCost.toFixed(2);
            document.getElementById('display_total').innerText    = "৳ " + total.toLocaleString('en-IN');
            document.getElementById('btn_total').innerText        = " ৳ " + total.toLocaleString('en-IN');

            document.getElementById('landing_cart_data').value = JSON.stringify(buildCartData());
        }

        function scrollToCheckout() {
            document.getElementById('checkout-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelector('input[name="billing_name"]').focus();
        }

        updateTotal();

        /* ── Live countdown ── */
        <?php if(!empty($pageConfig['countdown_date'])): ?>
        (function() {
            const endTime = new Date("<?php echo htmlspecialchars($pageConfig['countdown_date']); ?>").getTime();
            function tick() {
                const diff = endTime - Date.now();
                if (diff <= 0) {
                    ['cd-days','cd-hours','cd-mins','cd-secs'].forEach(id => {
                        const el = document.getElementById(id); if (el) el.textContent = '00';
                    });
                    return;
                }
                const d = Math.floor(diff / 86400000);
                const h = Math.floor((diff % 86400000) / 3600000);
                const m = Math.floor((diff % 3600000)  / 60000);
                const s = Math.floor((diff % 60000)    / 1000);
                const pad = n => String(n).padStart(2,'0');
                document.getElementById('cd-days').textContent  = pad(d);
                document.getElementById('cd-hours').textContent = pad(h);
                document.getElementById('cd-mins').textContent  = pad(m);
                document.getElementById('cd-secs').textContent  = pad(s);
            }
            tick();
            setInterval(tick, 1000);
        })();
        <?php endif; ?>

        /* ── Gallery carousel ── */
        <?php $galleryImages = $pageConfig['gallery_images'] ?? []; ?>
        <?php if(count($galleryImages) > 1): ?>
        (function() {
            const track  = document.getElementById('lp-track');
            const dotsEl = document.getElementById('lp-dots');
            const total  = <?php echo count($galleryImages); ?>;
            let cur = 0;

            // Build dots
            for (let i = 0; i < total; i++) {
                const d = document.createElement('button');
                d.className = 'w-2 h-2 rounded-full transition-all ' + (i === 0 ? 'bg-white scale-125' : 'bg-white/50');
                d.onclick = () => goTo(i);
                dotsEl.appendChild(d);
            }

            function goTo(n) {
                cur = (n + total) % total;
                track.style.transform = 'translateX(-' + (cur * 100) + '%)';
                dotsEl.querySelectorAll('button').forEach((d, i) => {
                    d.className = 'w-2 h-2 rounded-full transition-all ' + (i === cur ? 'bg-white scale-125' : 'bg-white/50');
                });
            }

            window.lpSlide = dir => goTo(cur + dir);

            // Auto-advance every 4 seconds
            setInterval(() => goTo(cur + 1), 4000);

            // Touch swipe
            let tx = 0;
            track.addEventListener('touchstart', e => { tx = e.touches[0].clientX; }, {passive:true});
            track.addEventListener('touchend',   e => {
                const dx = e.changedTouches[0].clientX - tx;
                if (Math.abs(dx) > 40) goTo(cur + (dx < 0 ? 1 : -1));
            }, {passive:true});
        })();
        <?php endif; ?>
    </script>
    <script>
        /* ── Meta Pixel events (landing page) ── */
        (function () {
            var viaGtm = <?= (stripos($siteSettings['pixel_head'] ?? '', 'googletagmanager.com/gtm.js') !== false) ? 'true' : 'false' ?>;
            function px(ev, params) {
                try {
                    window.dataLayer = window.dataLayer || [];
                    var payload = { event: ev };
                    for (var k in params) { payload[k] = params[k]; }
                    window.dataLayer.push(payload);
                } catch (e) {}
                try { if (!viaGtm && typeof fbq === 'function') fbq('track', ev, params); } catch (e) {}
            }
            function content() {
                var items = (typeof buildCartData === 'function') ? buildCartData() : [];
                items = Array.isArray(items) ? items : [];
                return {
                    content_ids: items.length ? items.map(function (i) { return String(i.id); }) : [String(productId)],
                    content_name: productName,
                    content_type: 'product',
                    num_items: items.reduce(function (n, i) { return n + (parseInt(i.qty, 10) || 1); }, 0) || 1,
                    value: (typeof getSubtotal === 'function') ? getSubtotal() : productPrice,
                    currency: 'BDT'
                };
            }
            px('ViewContent', { content_ids: [String(productId)], content_name: productName, content_type: 'product', value: productPrice, currency: 'BDT' });

            var form = document.getElementById('landing-checkout-form');
            if (form) {
                var started = false;
                ['focusin', 'input', 'change'].forEach(function (evt) {
                    form.addEventListener(evt, function () {
                        if (started) return;
                        started = true;
                        px('InitiateCheckout', content());
                    });
                });
            }
        })();
    </script>
</body>
</html>