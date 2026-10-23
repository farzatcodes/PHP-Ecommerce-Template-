<?php include_once 'includes/header.php'; ?>

<main class="bg-cream-soft min-h-[70vh] py-8">
    <div class="container mx-auto px-4 max-w-5xl">
        <h1 class="font-heading text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-shopping-cart text-[#DB1456]"></i> Shopping Cart
        </h1>
        
        <div class="flex flex-col lg:flex-row gap-6">
            
            <div class="w-full lg:w-2/3 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                
                <!-- Container for dynamic JS injection -->
                <div id="cart-page-container" class="flex flex-col gap-4">
                    <!-- Items inject here -->
                </div>
                
                <!-- Empty State -->
                <div id="cart-page-empty" class="hidden text-center py-12">
                    <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                        <i class="fas fa-shopping-basket text-5xl"></i>
                    </div>
                    <p class="text-gray-600 font-medium text-lg">Your cart is currently empty.</p>
                    <a href="index.php" class="mt-6 inline-block bg-[#DB1456] text-white px-8 py-3 rounded-lg font-bold hover:bg-[#B80E43] transition-all duration-300 transform hover:-translate-y-0.5 shadow-md">
                        Continue Shopping
                    </a>
                </div>
            </div>

            <div class="w-full lg:w-1/3">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sticky top-24">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Order Summary</h2>
                    
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-gray-600">Subtotal (<span id="cart-page-count">0</span> items)</span>
                        <span class="font-bold text-gray-900 text-lg">৳ <span id="cart-page-subtotal">0</span></span>
                    </div>
                    
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 mb-6 flex items-start gap-2">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <p class="text-xs text-gray-700 leading-relaxed">
                            Shipping and delivery costs will be calculated at checkout based on your delivery area.
                        </p>
                    </div>
                    
                    <a href="checkout.php" id="cart-page-checkout-btn" class="w-full flex justify-center items-center gap-2 bg-[#DB1456] hover:bg-[#B80E43] text-white font-bold py-3.5 px-4 rounded-lg transition-all duration-300 shadow-md transform hover:-translate-y-0.5 uppercase tracking-wide">
                        Proceed to Checkout <i class="fas fa-arrow-right text-sm"></i>
                    </a>
                </div>
            </div>

        </div>
    </div>
</main>

<script>
function renderCartPage() {
    let currentCart = JSON.parse(localStorage.getItem('wellcarebd_cart')) || [];
    const container = document.getElementById('cart-page-container');
    const emptyMsg = document.getElementById('cart-page-empty');
    const checkoutBtn = document.getElementById('cart-page-checkout-btn');
    
    container.innerHTML = '';
    let subtotal = 0;
    let count = 0;

    if(currentCart.length === 0) {
        container.classList.add('hidden');
        emptyMsg.classList.remove('hidden');
        checkoutBtn.classList.add('opacity-50', 'pointer-events-none');
    } else {
        container.classList.remove('hidden');
        emptyMsg.classList.add('hidden');
        checkoutBtn.classList.remove('opacity-50', 'pointer-events-none');

        currentCart.forEach((item, index) => {
            subtotal += (item.price * item.qty);
            count += item.qty;
            const html = `
                <div class="flex flex-col sm:flex-row items-center gap-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0 relative group">
                    
                    <!-- Image -->
                    <div class="w-24 h-24 bg-white rounded border border-gray-200 p-1 flex-shrink-0 relative">
                        <img src="${item.image}" class="w-full h-full object-contain">
                    </div>
                    
                    <!-- Info -->
                    <div class="flex-grow text-center sm:text-left">
                        <h3 class="font-semibold text-gray-800 text-[15px] mb-1.5 leading-snug pr-0 sm:pr-8">${item.name}</h3>
                        <div class="text-[#DB1456] font-black text-[16px]">৳ ${item.price}</div>
                    </div>
                    
                    <!-- Controls -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-2 sm:mt-0">
                        <div class="flex items-center border border-gray-200 rounded-lg bg-white shadow-sm overflow-hidden">
                            <button type="button" onclick="updateCartPageQty(${index}, -1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors text-lg font-medium cursor-pointer">-</button>
                            <input type="text" value="${item.qty}" class="w-10 h-8 text-center text-sm border-x border-gray-200 bg-gray-50 font-bold focus:outline-none text-gray-700" readonly>
                            <button type="button" onclick="updateCartPageQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors text-lg font-medium cursor-pointer">+</button>
                        </div>
                        
                        <div class="font-bold text-gray-900 w-20 text-right hidden sm:block">
                            ৳ ${item.price * item.qty}
                        </div>
                    </div>

                    <!-- Remove Button (Absolute top right on desktop, normal flow on mobile) -->
                    <button type="button" onclick="removeCartPageItem(${index})" class="absolute top-2 right-2 sm:relative sm:top-0 sm:right-0 w-8 h-8 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-brand-50 rounded-full transition-all" title="Remove Item">
                        <i class="far fa-trash-alt text-lg"></i>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        });
    }

    document.getElementById('cart-page-count').innerText = count;
    document.getElementById('cart-page-subtotal').innerText = subtotal;
    
    // Sync with global header logic located in footer.php
    if(typeof updateHeaderCart === 'function') updateHeaderCart();
}

function updateCartPageQty(index, change) {
    let currentCart = JSON.parse(localStorage.getItem('wellcarebd_cart')) || [];
    if (currentCart[index].qty + change > 0) {
        currentCart[index].qty += change;
        localStorage.setItem('wellcarebd_cart', JSON.stringify(currentCart));
        renderCartPage();
    }
}

function removeCartPageItem(index) {
    if(confirm('Are you sure you want to remove this item from your cart?')) {
        let currentCart = JSON.parse(localStorage.getItem('wellcarebd_cart')) || [];
        currentCart.splice(index, 1);
        localStorage.setItem('wellcarebd_cart', JSON.stringify(currentCart));
        renderCartPage();
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', renderCartPage);
</script>

<?php include_once 'includes/footer.php'; ?>