<?php
include_once 'includes/header.php';
?>

<main class="bg-cream-soft min-h-screen py-4 md:py-8">
    <div class="container mx-auto px-2 md:px-4 flex justify-center">
        
        <!-- Checkout Form Container (Constrained width for mobile-like feel on desktop) -->
        <form action="process_checkout.php" method="POST" class="w-full max-w-md bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden" onsubmit="return validateCheckout()">
            
            <!-- Hidden input to pass dynamic cart data to PHP backend -->
            <input type="hidden" name="cart_data" id="cart_data_input">

            <!-- Section 1: Cart Overview -->
            <div class="p-4 border-b-4 border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-heading text-lg font-bold text-gray-900">Cart Overview</h2>
                    <span class="text-sm font-bold text-gray-800">৳<span id="subtotal">0</span> (<span id="cart_count">0</span> items)</span>
                </div>

                <!-- Dynamic Cart Items Container -->
                <div id="cart-items-container" class="flex flex-col gap-4">
                    <!-- Items will be injected here by JavaScript -->
                </div>
                
                <!-- Empty Cart Fallback -->
                <div id="empty-cart-message" class="hidden text-center py-6 text-gray-500">
                    <i class="fas fa-shopping-cart text-4xl mb-3 text-gray-300"></i>
                    <p class="font-medium">Your cart is currently empty.</p>
                    <a href="index.php" class="text-[#DB1456] hover:underline text-sm font-bold mt-2 inline-block">Continue Shopping</a>
                </div>
            </div>

            <!-- Section 2: Promo Code -->
            <div class="flex border-b-4 border-gray-100">
                <input type="text" placeholder="If you have a Promo Code, Enter Here..." class="flex-1 p-3 text-[13px] text-gray-700 focus:outline-none placeholder-gray-400">
                <button type="button" class="bg-[#DB1456] text-white font-bold px-6 py-3 text-sm hover:bg-[#B80E43] transition-colors">
                    Apply
                </button>
            </div>

            <!-- Section 3: Customer Information -->
            <div class="p-4 border-b-4 border-gray-100">
                <h2 class="font-heading text-[17px] font-bold text-gray-900 mb-4">Customer Information</h2>
                
                <div class="space-y-3">
                    <!-- Name Input -->
                    <div class="flex border border-gray-300 rounded-sm overflow-hidden focus-within:border-gray-400 transition-colors">
                        <div class="w-10 bg-gray-100 flex items-center justify-center border-r border-gray-300 text-gray-800">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <input type="text" name="customer_name" placeholder="আপনার নাম লিখুন..." required class="flex-1 p-2.5 text-[14px] text-gray-700 focus:outline-none bengali-text placeholder-gray-400">
                    </div>

                    <!-- Phone Input -->
                    <div class="flex border border-gray-300 rounded-sm overflow-hidden focus-within:border-gray-400 transition-colors">
                        <div class="w-10 bg-gray-100 flex items-center justify-center border-r border-gray-300 text-gray-800">
                            <i class="fas fa-phone-alt text-sm"></i>
                        </div>
                        <input type="tel" name="customer_phone" placeholder="মোবাইল নাম্বার লিখুন..." required class="flex-1 p-2.5 text-[14px] text-gray-700 focus:outline-none bengali-text placeholder-gray-400">
                    </div>

                    <!-- Address Input -->
                    <div class="flex border border-gray-300 rounded-sm overflow-hidden focus-within:border-gray-400 transition-colors">
                        <div class="w-10 bg-gray-100 flex items-center justify-center border-r border-gray-300 text-gray-800">
                            <i class="fas fa-address-book text-sm"></i>
                        </div>
                        <input type="text" name="customer_address" placeholder="আপনার ঠিকানা লিখুন..." required class="flex-1 p-2.5 text-[14px] text-gray-700 focus:outline-none bengali-text placeholder-gray-400">
                    </div>

                    <!-- Comments Textarea -->
                    <textarea name="customer_comment" placeholder="কোন মন্তব্য থাকলে এখানে লিখুন" rows="2" class="w-full border border-gray-300 rounded-sm p-2.5 text-[14px] text-gray-700 focus:outline-none focus:border-gray-400 transition-colors bengali-text bg-gray-50 placeholder-gray-400"></textarea>
                </div>

                <!-- Area Selection -->
                <div class="flex items-center gap-4 mt-4">
                    <span class="text-[13px] text-gray-800">Select area <span class="text-red-500">*</span></span>
                    
                    <label class="flex items-center gap-1.5 cursor-pointer group">
                        <input type="radio" name="shipping_area" value="60" class="w-4 h-4 accent-[#DB1456]" onchange="updateTotal()">
                        <span class="text-[14px] font-bold text-gray-900">Dhaka city</span>
                    </label>
                    
                    <label class="flex items-center gap-1.5 cursor-pointer group">
                        <input type="radio" name="shipping_area" value="150" class="w-4 h-4 accent-[#DB1456]" checked onchange="updateTotal()">
                        <span class="text-[14px] font-bold text-gray-900">Outside dhaka</span>
                    </label>
                </div>
            </div>

            <!-- Section 4: Payment Method -->
            <div class="p-4 bg-white">
                <span class="text-[14px] font-bold text-gray-800 block mb-4">Payment Method <span class="text-red-500">*</span></span>
                
                <div class="grid grid-cols-1 gap-3 mb-6">
                    <!-- COD Option (Only Option Now) -->
                    <label class="relative flex items-center py-4 px-4 bg-green-50 border-2 border-green-500 rounded-lg cursor-pointer transition-colors shadow-sm">
                        <input type="radio" name="payment_method" value="cod" class="sr-only peer" checked>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                            <span class="text-[15px] font-bold text-green-800">Cash on Delivery</span>
                        </div>
                        <!-- Green Checkmark -->
                        <div class="absolute right-4 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center text-white text-xs shadow-sm">
                            <i class="fas fa-check"></i>
                        </div>
                    </label>
                </div>

                <!-- Total Display -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200 flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Total Payable:</span>
                    <h3 class="text-2xl font-black text-[#DB1456]">
                        ৳<span id="grand_total">3050</span>
                    </h3>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-center mb-2">
                    <button type="submit" id="submit-btn" class="w-full bg-[#DB1456] hover:bg-[#B80E43] text-white font-bold text-[16px] py-4 px-8 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 uppercase tracking-wide">
                        Confirm order
                    </button>
                </div>
            </div>
            
        </form>
    </div>
</main>

<script>
    let cart = JSON.parse(localStorage.getItem('wellcarebd_cart')) || [];

    function renderCart() {
        const container = document.getElementById('cart-items-container');
        const emptyMessage = document.getElementById('empty-cart-message');
        const submitBtn = document.getElementById('submit-btn');
        
        container.innerHTML = '';
        let subtotal = 0;
        let totalItems = 0;

        if (cart.length === 0) {
            container.classList.add('hidden');
            emptyMessage.classList.remove('hidden');
            if(submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed', 'transform-none');
            }
        } else {
            container.classList.remove('hidden');
            emptyMessage.classList.add('hidden');
            if(submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'transform-none');
            }

            cart.forEach((item, index) => {
                subtotal += (item.price * item.qty);
                totalItems += item.qty;

                const itemHTML = `
                    <div class="flex flex-col gap-2 p-2 border border-gray-50 rounded-lg hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-1">
                            <span class="font-bold text-[15px] text-gray-900 leading-tight">${index + 1}.</span>
                            <span class="text-[14px] text-gray-800 leading-tight line-clamp-2">${item.name}</span>
                        </div>
                        
                        <div class="flex justify-between items-center mt-1 pl-4">
                            <div class="w-12 h-12 flex-shrink-0 bg-white border border-gray-100 rounded shadow-sm p-0.5">
                                <img src="${item.image || 'https://placehold.co/100x100/e2e8f0/1e293b?text=Item'}" alt="${item.name}" class="w-full h-full object-contain">
                            </div>
                            
                            <!-- Dynamic Quantity Control -->
                            <div class="flex items-center border border-gray-200 rounded bg-white shadow-sm">
                                <button type="button" onclick="updateQty(${index}, -1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer text-lg font-medium">-</button>
                                <input type="text" value="${item.qty}" class="w-10 h-8 text-center text-sm border-x border-gray-200 bg-gray-50 font-bold focus:outline-none text-gray-700" readonly>
                                <button type="button" onclick="updateQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer text-lg font-medium">+</button>
                            </div>
                            
                            <span class="font-bold text-[15px] text-[#DB1456]">৳ ${item.price * item.qty}</span>
                            
                            <!-- Remove Item -->
                            <button type="button" onclick="removeItem(${index})" class="text-gray-300 hover:text-red-500 transition-colors" title="Remove item">
                                <i class="far fa-times-circle text-xl"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', itemHTML);
            });
        }

        // Update DOM totals
        document.getElementById('subtotal').innerText = subtotal;
        document.getElementById('cart_count').innerText = totalItems;
        
        // Populate hidden input so PHP gets the cart array on submit
        document.getElementById('cart_data_input').value = JSON.stringify(cart);
        
        updateTotal(subtotal);
    }

    function updateQty(index, change) {
        if (cart[index].qty + change > 0) {
            cart[index].qty += change;
            saveAndRender();
        }
    }

    function removeItem(index) {
        if (confirm('Are you sure you want to remove this item?')) {
            cart.splice(index, 1);
            saveAndRender();
        }
    }

    function saveAndRender() {
        localStorage.setItem('wellcarebd_cart', JSON.stringify(cart));
        renderCart();
    }

    function updateTotal(currentSubtotal = null) {
        let subtotal = currentSubtotal;
        if (subtotal === null) {
            subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        }
        
        const shippingOptions = document.getElementsByName('shipping_area');
        let shippingCost = 150; 
        
        for (const option of shippingOptions) {
            if (option.checked) {
                shippingCost = parseInt(option.value);
                break;
            }
        }
        
        const grandTotal = subtotal + shippingCost;
        document.getElementById('grand_total').innerText = grandTotal;
    }

    function validateCheckout() {
        if (cart.length === 0) {
            alert("Your cart is empty! Please add items before checking out.");
            return false;
        }
        return true;
    }

    // Run on page load
    renderCart();
    (function () {
        var items = cart || [];
        if (!items.length || !window.trackPixel) return;
        trackPixel('InitiateCheckout', {
            content_ids: items.map(function (i) { return String(i.id); }),
            content_type: 'product',
            num_items: items.reduce(function (n, i) { return n + i.qty; }, 0),
            value: items.reduce(function (s, i) { return s + i.price * i.qty; }, 0),
            currency: 'BDT'
        });
    })();
</script>

<?php
include_once 'includes/footer.php';
?>