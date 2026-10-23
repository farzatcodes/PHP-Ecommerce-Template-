<?php
// Load pixel/tracking settings — skip if already loaded by the calling page
if (!isset($siteSettings)) {
    $settingsFile = __DIR__ . '/../settings.json';
    $siteSettings = file_exists($settingsFile)
        ? (json_decode(file_get_contents($settingsFile), true) ?? [])
        : [];
}

// Load categories for the site navigation — kept in sync with the admin panel
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/image-helpers.php';
require_once __DIR__ . '/pixel-loader.php';
$navCategories = loadCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WellCareBD - Trusted Online Shop</title>
    <meta name="description" content="WellCareBD - your trusted online shop in Bangladesh. Cash on delivery nationwide, fast delivery.">

    <meta name="theme-color" content="#0A0A0A">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/branding/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/branding/favicon-192.png">
    <link rel="apple-touch-icon" href="/assets/branding/apple-touch-icon.png">

    <!-- Resource hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap">
    <link rel="preload" href="/assets/css/output.css" as="style">
    <link rel="preload" href="/font/HindSiliguri-Regular.woff2" as="font" type="font/woff2" crossorigin>
    <?php if (!empty($preloadHeroImage)): ?>
    <link rel="preload" href="<?= htmlspecialchars($preloadHeroImage) ?>" as="image" fetchpriority="high"<?php if (!empty($preloadHeroImageSrcset)): ?> imagesrcset="<?= htmlspecialchars($preloadHeroImageSrcset) ?>" imagesizes="<?= htmlspecialchars($preloadHeroImageSizes ?? '100vw') ?>"<?php endif; ?>>
    <?php endif; ?>

    <!-- Local Compiled Tailwind CSS (Includes your fonts and custom styles) -->
    <link rel="stylesheet" href="/assets/css/output.css">

    <!-- Font Awesome: load non-blocking to avoid render delay -->
    <link rel="preload" href="/assets/fontawesome/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/assets/fontawesome/css/all.min.css"></noscript>

    <?php echo renderDeferredPixelLoader($siteSettings); ?>
</head>
<body class="bg-cream-soft text-gray-800">
<?php echo renderPixelBody($siteSettings); ?>

<!-- Top Notification Ticker -->
<div class="bg-brand-600 text-white py-1.5 w-full overflow-hidden">
    <div class="container mx-auto px-4 relative">
        <div class="whitespace-nowrap animate-[ticker_20s_linear_infinite] inline-block w-full">
            <span class="bengali-text text-sm">
                wellcarebd.info অনলাইন শপ এ আপনাকে স্বাগতম । বাংলাদেশের বিশ্বস্ত অনলাইন শপ । সারা দেশে ক্যাশ অন ডেলিভারি (৪৮ থেকে ৭২ ঘণ্টার মধ্যে নিশ্চিত ডেলিভারি) সকাল ১০ টা থেকে রাত ১০ পর্যন্ত।
            </span>
        </div>
    </div>
</div>

<!-- Main Header Section (black — the logo is cream/pink and needs a dark surface) -->
<header class="bg-ink py-3 md:py-4 border-b border-white/10 sticky top-0 z-40">
    <div class="container mx-auto px-4 flex flex-wrap items-center justify-between gap-4">

        <!-- Left Side: Hamburger & Logo -->
        <div class="flex items-center gap-2 md:gap-0">
            <!-- Hamburger Menu Button (Mobile Only) -->
            <button id="mobile-menu-btn" aria-label="Open menu" class="md:hidden w-11 h-11 -ml-2 flex items-center justify-center text-cream hover:text-brand-400 transition-colors">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <!-- Logo -->
            <div class="flex-shrink-0 md:w-52">
                <a href="/" class="block group" aria-label="WellCareBD home">
                    <img src="/assets/branding/logo-wordmark.png" alt="WellCareBD" width="560" height="112"
                         class="h-8 md:h-10 w-auto transition-transform duration-300 group-hover:scale-105">
                </a>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="flex-grow max-w-2xl mx-auto hidden md:block">
            <form action="search.php" method="GET" class="relative w-full group">
                <input
                    type="text"
                    name="q"
                    placeholder="Search for products, brands and more..."
                    aria-label="Search products"
                    class="w-full bg-white/10 border border-white/15 rounded-full py-2.5 px-6 pr-12 text-cream placeholder-white/50 text-sm focus:bg-white/15 transition-all duration-300 search-input"
                    required
                >
                <button type="submit" aria-label="Search" class="absolute right-1 top-1 bottom-1 w-11 flex items-center justify-center text-white/70 hover:text-white hover:bg-brand-600 rounded-full transition-colors duration-300">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>

        <!-- Right Side: Cart Info -->
        <div class="flex-shrink-0 flex items-center justify-end w-auto md:w-52 gap-2 md:gap-4">
            <!-- Mobile Search Icon -->
            <label for="mobile-search-input" aria-label="Search" class="md:hidden w-11 h-11 flex items-center justify-center text-cream hover:text-brand-400 transition-colors cursor-pointer">
                <i class="fas fa-search text-xl"></i>
            </label>

            <a href="cart.php" class="flex items-center space-x-3 cursor-pointer group" aria-label="Cart">
                <!-- Cart Icon with Badge -->
                <div class="relative w-11 h-11 bg-white/10 border border-white/10 rounded-full flex items-center justify-center text-cream group-hover:bg-brand-600 group-hover:border-brand-600 group-hover:text-white transition-all duration-300 transform group-hover:-translate-y-0.5">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <span id="headerCartCount" class="absolute -top-1 -right-1 bg-brand-600 group-hover:bg-white group-hover:text-brand-600 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-ink transition-all duration-300">
                        0
                    </span>
                </div>

                <!-- Cart Text & Total -->
                <div class="hidden sm:block text-left">
                    <div class="text-[11px] text-white/60 uppercase font-bold tracking-wide leading-tight group-hover:text-brand-400 transition-colors">Total Price</div>
                    <div class="text-sm font-black text-cream leading-tight mt-0.5"><span id="headerCartTotal">0</span> TK</div>
                </div>
            </a>
        </div>

    </div>
</header>

<!-- Desktop Navigation Menu Section -->
<nav class="hidden md:block bg-ink-soft text-cream border-b border-brand-600/70 relative z-30" aria-label="Categories">
    <div class="container mx-auto px-4">
        <ul class="flex items-center space-x-8 overflow-x-auto whitespace-nowrap scrollbar-hide h-12 w-full">

            <li class="relative group h-full flex items-center border-r border-white/15 pr-6 shrink-0">
                <a href="#" class="flex items-center text-sm font-medium text-brand-300 hover:text-white transition-colors bengali-text">
                    ৫০০ টাকা ডিসকাউন্ট
                    <i class="fas fa-chevron-down ml-1 text-xs transition-transform group-hover:rotate-180"></i>
                </a>

                <div class="absolute top-full left-0 mt-0 w-48 bg-white text-gray-800 shadow-xl border border-gray-200 rounded-b-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top group-hover:scale-100 scale-95 z-50">
                    <ul class="py-2">
                        <li><a href="#" class="block px-4 py-2 text-sm hover:bg-brand-50 hover:text-brand-600 transition-colors font-medium">Option 1</a></li>
                        <li><a href="#" class="block px-4 py-2 text-sm hover:bg-brand-50 hover:text-brand-600 transition-colors font-medium">Option 2</a></li>
                    </ul>
                </div>
            </li>

            <?php foreach ($navCategories as $navCat): ?>
            <li class="h-full flex items-center shrink-0"><a href="category.php?slug=<?= htmlspecialchars($navCat['slug']) ?>" class="h-full flex items-center border-b-2 border-transparent hover:border-brand-500 text-[13px] font-medium uppercase tracking-wide text-cream/90 hover:text-white transition-colors"><?= htmlspecialchars($navCat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<!-- Mobile Drawer Menu -->
<div id="mobile-menu-overlay" class="fixed inset-0 bg-black/70 z-[60] hidden transition-opacity duration-300 opacity-0"></div>
<div id="mobile-menu-drawer" class="fixed inset-y-0 left-0 w-3/4 max-w-sm bg-white z-[70] transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col shadow-2xl">

    <div class="bg-ink px-4 py-4 border-b border-brand-600/70 flex items-center justify-between">
        <img src="/assets/branding/logo-wordmark.png" alt="WellCareBD" width="560" height="112" class="h-8 w-auto">
        <button id="close-menu-btn" aria-label="Close menu" class="text-cream hover:text-white text-xl w-11 h-11 flex items-center justify-center rounded-full bg-white/10 hover:bg-brand-600 transition-colors">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="flex-grow overflow-y-auto bg-white">
        <ul class="flex flex-col py-2">
            <li>
                <a href="#" class="flex items-center px-5 py-3.5 text-gray-800 hover:bg-brand-50 hover:text-brand-600 border-b border-gray-100 transition-colors bengali-text font-bold">
                    <i class="fas fa-tags w-6 text-brand-600 mr-2"></i> ৫০০ টাকা ডিসকাউন্ট
                </a>
            </li>
            <?php foreach ($navCategories as $navCat): ?>
            <li>
                <a href="category.php?slug=<?= htmlspecialchars($navCat['slug']) ?>" class="flex items-center px-5 py-3.5 text-gray-800 hover:bg-brand-50 hover:text-brand-600 border-b border-gray-100 transition-colors text-sm font-bold uppercase tracking-wide">
                    <?= htmlspecialchars($navCat['name']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Mobile Search Bar -->
<div class="md:hidden bg-ink-soft p-3 border-b border-white/10 relative z-20">
    <form action="search.php" method="GET" class="relative w-full">
        <input
            type="text"
            name="q"
            id="mobile-search-input"
            placeholder="Search Product..."
            aria-label="Search products"
            class="w-full bg-white/10 border border-white/15 rounded-full py-2.5 px-4 pr-12 text-cream placeholder-white/50 text-sm focus:outline-none focus:border-brand-500 focus:bg-white/15 transition-colors"
            required
        >
        <button type="submit" aria-label="Search" class="absolute right-1 top-1 bottom-1 w-11 flex items-center justify-center text-white/70 rounded-full hover:bg-brand-600 hover:text-white transition-colors">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- Mobile Menu Logic -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('mobile-menu-btn');
        const closeBtn = document.getElementById('close-menu-btn');
        const overlay = document.getElementById('mobile-menu-overlay');
        const drawer = document.getElementById('mobile-menu-drawer');

        function openMenu() {
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                drawer.classList.remove('-translate-x-full');
                document.body.style.overflow = 'hidden';
            }, 10);
        }

        function closeMenu() {
            overlay.classList.add('opacity-0');
            drawer.classList.add('-translate-x-full');
            document.body.style.overflow = '';
            setTimeout(() => { overlay.classList.add('hidden'); }, 300);
        }

        menuBtn.addEventListener('click', openMenu);
        closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);
    });
</script>