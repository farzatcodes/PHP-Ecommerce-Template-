<div class="bg-white p-4 md:p-6 rounded-xl shadow-sm mt-6">
    <!-- Tabs Header -->
    <div class="flex border-b border-gray-200 mb-6 flex-wrap gap-y-2">
        <button onclick="openTab(event, 'description')" class="tab-btn active px-6 py-3 text-[#DB1456] border-b-2 border-[#DB1456] font-bold bengali-text text-base hover:text-[#DB1456] transition-colors focus:outline-none">
            পণ্যের বিস্তারিত
        </button>
        <button onclick="openTab(event, 'policy')" class="tab-btn px-6 py-3 text-gray-600 border-b-2 border-transparent font-medium bengali-text text-base hover:text-[#DB1456] transition-colors focus:outline-none">
            ডেলিভারি এবং রিটার্ন পলিসি
        </button>
        <button onclick="openTab(event, 'reviews')" class="tab-btn px-6 py-3 text-gray-600 border-b-2 border-transparent font-medium bengali-text text-base hover:text-[#DB1456] transition-colors focus:outline-none">
            রিভিউ
        </button>
    </div>

    <!-- Tabs Content -->
    <div id="description" class="tab-content block">
        <div class="text-[13px] text-black leading-relaxed font-medium">
            <?= isset($product['description']) ? $product['description'] : '<p class="text-gray-500">কোন বিবরণ পাওয়া যায়নি।</p>' ?>
        </div>
    </div>

    <div id="policy" class="tab-content hidden">
        <div class="text-[13px] text-black leading-relaxed font-medium bengali-text space-y-3">
            <h3 class="font-bold text-[15px] mb-2">ডেলিভারি পলিসি:</h3>
            <p>১. ঢাকা সিটির ভেতরে ক্যাশ অন ডেলিভারি (Delivery Charge 60 TK)।</p>
            <p>২. ঢাকা সিটির বাইরে কুরিয়ার সার্ভিসের মাধ্যমে ডেলিভারি দেওয়া হয় (Delivery Charge 130 TK)।</p>
            
            <h3 class="font-bold text-[15px] mt-4 mb-2">রিটার্ন পলিসি:</h3>
            <p>১. প্রডাক্ট হাতে পাওয়ার পর অবশ্যই ডেলিভারি ম্যানের সামনে চেক করে রিসিভ করবেন।</p>
            <p>২. প্রডাক্টে কোন সমস্যা থাকলে বা পছন্দ না হলে সাথে সাথে রিটার্ন করতে পারবেন (শুধুমাত্র ডেলিভারি চার্জ প্রযোজ্য)।</p>
        </div>
    </div>

    <div id="reviews" class="tab-content hidden">
        <div class="py-4">
            <?php if (!empty($product['reviews']) && is_array($product['reviews'])): ?>
                <div class="space-y-4">
                    <?php foreach ($product['reviews'] as $review): ?>
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="text-[#DB1456] flex text-sm">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="<?= $i <= $review['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($review['user']) ?></span>
                                <span class="text-xs text-gray-400 ml-auto"><?= htmlspecialchars($review['date']) ?></span>
                            </div>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($review['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <p class="text-gray-500 bengali-text">এখনো কোন রিভিউ নেই।</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function openTab(evt, tabName) {
        // Declare all variables
        var i, tabcontent, tablinks;

        // Get all elements with class="tab-content" and hide them
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Get all elements with class="tab-btn" and remove the active styling
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" text-[#DB1456] border-[#DB1456] font-bold active", " text-gray-600 border-transparent font-medium");
        }

        // Show the current tab, and add active styling to the button that opened the tab
        document.getElementById(tabName).style.display = "block";
        evt.currentTarget.className = evt.currentTarget.className.replace(" text-gray-600 border-transparent font-medium", " text-[#DB1456] border-[#DB1456] font-bold active");
    }
</script>