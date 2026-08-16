// Buyer Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function () {
    initializeBuyerDashboard();
    updateCartBadge();
    loadProfileData();
    loadWishlist();

    window.addEventListener('hashchange', function () {
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash + '-section')) {
            showSection(hash);
        }
    });

    setTimeout(function () {
        const hash = window.location.hash.substring(1);
        if (hash && document.getElementById(hash + '-section')) {
            showSection(hash);
        }
    }, 100);
});

function initializeBuyerDashboard() {
    const hash = window.location.hash.substring(1);

    if (hash && document.getElementById(hash + '-section')) {
        showSection(hash);
    } else {
        if (document.getElementById('dashboard-section')) {
            showSection('dashboard');
        }
    }

    document.querySelectorAll('.menu-link').forEach(link => {
        link.addEventListener('click', function (e) {
            if (this.getAttribute('href') !== '#') {
                return;
            }
            e.preventDefault();
            const section = this.dataset.section;
            if (section) {
                showSection(section);
            }
        });
    });
}

function showSection(sectionName) {
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });

    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }

    document.querySelectorAll('.menu-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.section === sectionName) {
            link.classList.add('active');
        }
    });

    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });

    if (sectionName === 'wishlist') {
        loadWishlist();
    }
}

function loadProfileData() {
    const profilePhotoEl = document.getElementById('profilePhoto');
    const profileNameEl = document.getElementById('profileName');
    const profileEmailEl = document.getElementById('profileEmail');
    const profilePhoneEl = document.getElementById('profilePhone');
    const profileLocationEl = document.getElementById('profileLocation');
    const profileAddressEl = document.getElementById('profileAddress');

    const uname = (window.USER_NAME || 'Buyer').trim() || 'Buyer';
    const uemail = (window.USER_EMAIL || '').trim();

    if (profilePhotoEl) {
        const encoded = encodeURIComponent(uname || 'Buyer');
        profilePhotoEl.src = `https://ui-avatars.com/api/?name=${encoded}&background=4CAF50&color=fff&size=150`;
        profilePhotoEl.alt = 'Buyer Profile';
    }

    if (profileNameEl && !profileNameEl.value) profileNameEl.value = uname;
    if (profileEmailEl && !profileEmailEl.value) profileEmailEl.value = uemail || 'buyer@example.com';
    if (profilePhoneEl && !profilePhoneEl.value) profilePhoneEl.value = '+94 77 123 4567';
    if (profileLocationEl && !profileLocationEl.value) profileLocationEl.value = 'Colombo';
    if (profileAddressEl && !profileAddressEl.value) profileAddressEl.value = '123, Main Street, Colombo 07, Sri Lanka';
}

function updateProfile() {
    const name = document.getElementById('profileName')?.value?.trim();
    const email = document.getElementById('profileEmail')?.value?.trim();
    const phone = document.getElementById('profilePhone')?.value?.trim();
    const city = document.getElementById('profileLocation')?.value?.trim();
    const address = document.getElementById('profileAddress')?.value?.trim();

    if (!name || !email || !phone || !city || !address) {
        showNotification('Please fill all required fields', 'error');
        return;
    }
    showNotification('Profile updated successfully!', 'success');
}

function uploadPhoto() {
    let input = document.getElementById('photoUploadInput');
    if (!input) {
        input = document.createElement('input');
        input.type = 'file';
        input.id = 'photoUploadInput';
        input.accept = 'image/*';
        input.style.display = 'none';
        document.body.appendChild(input);
    }

    input.onchange = function (e) {
        const file = e.target.files[0];
        if (file) {
            if (!file.type.startsWith('image/')) {
                showNotification('Please select a valid image file', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Image size should be less than 5MB', 'error');
                return;
            }
            const reader = new FileReader();
            reader.onload = function (ev) {
                const profilePhoto = document.getElementById('profilePhoto');
                if (profilePhoto) {
                    profilePhoto.src = ev.target.result;
                    showNotification('Photo uploaded successfully!', 'success');
                }
            };
            reader.onerror = function () {
                showNotification('Failed to read image file', 'error');
            };
            reader.readAsDataURL(file);
        }
    };

    input.click();
}

function filterProducts() {
    const searchInput = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const categoryFilter = document.getElementById('categoryFilter')?.value.toLowerCase() || '';
    const locationFilter = document.getElementById('locationFilter')?.value.toLowerCase() || '';
    const priceFilter = document.getElementById('priceFilter')?.value || '';

    const productCards = document.querySelectorAll('.product-card');

    productCards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const farmer = card.getAttribute('data-farmer') || '';
        const category = card.getAttribute('data-category') || '';
        const location = card.getAttribute('data-location') || '';
        const price = parseFloat(card.getAttribute('data-price')) || 0;

        const matchesSearch = searchInput === '' || name.includes(searchInput) || farmer.includes(searchInput);
        const matchesCategory = categoryFilter === '' || category === categoryFilter;
        const matchesLocation = locationFilter === '' || location.includes(locationFilter);

        let matchesPrice = true;
        if (priceFilter) {
            if (priceFilter === '0-100') {
                matchesPrice = price < 100;
            } else if (priceFilter === '100-200') {
                matchesPrice = price >= 100 && price <= 200;
            } else if (priceFilter === '200-500') {
                matchesPrice = price >= 200 && price <= 500;
            } else if (priceFilter === '500+') {
                matchesPrice = price > 500;
            }
        }

        if (matchesSearch && matchesCategory && matchesLocation && matchesPrice) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    const visibleCards = document.querySelectorAll('.product-card[style="display: block;"]');
    const productsGrid = document.getElementById('productsGrid');

    if (visibleCards.length === 0 && productsGrid) {
        const existingMessage = productsGrid.querySelector('.no-results-message');
        if (!existingMessage) {
            const noResults = document.createElement('div');
            noResults.className = 'no-results-message buyer-products-empty-state';
            noResults.innerHTML = `
                <div class="buyer-products-empty-icon">🔍</div>
                <h3>No products found</h3>
                <p>Try adjusting your search or filters</p>
            `;
            productsGrid.appendChild(noResults);
        }
    } else {
        const message = productsGrid?.querySelector('.no-results-message');
        if (message) message.remove();
    }
}

function addToCart(productId, productName, price, maxQuantity) {
    console.log('addToCart called:', productId, productName, price, maxQuantity);

    let btn = null;
    try {
        btn = (typeof event !== 'undefined' && event?.target) ||
            document.querySelector(`.product-card[data-id="${productId}"] .btn-add-cart`) ||
            document.querySelector(`.product-card[data-wishlist-product="${productId}"] .btn-add-cart-from-wishlist`) ||
            document.querySelector(`.product-card[data-id="${productId}"] button`) ||
            null;
    } catch (e) {
        btn = null;
    }

    const originalText = btn?.textContent;
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Adding...';
    }

    const productCard = document.querySelector(`.product-card[data-id="${productId}"]`) ||
        document.querySelector(`.product-card[data-wishlist-product="${productId}"]`);

    let imageFile = '';
    if (productCard) {
        imageFile = productCard.getAttribute('data-image') || '';
        if (!imageFile) {
            const imgEl = productCard.querySelector('.product-image img');
            const src = imgEl?.getAttribute('src') || '';
            if (src && !/default-product\.svg$/i.test(src)) {
                try {
                    const urlParts = src.split('/');
                    imageFile = urlParts[urlParts.length - 1];
                    if (imageFile.includes('?')) {
                        imageFile = imageFile.split('?')[0];
                    }
                } catch (e) {
                    imageFile = '';
                }
            }
        }
    }

    const fallbackEmoji = productCard?.querySelector('.product-placeholder')?.textContent || '🌱';

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('product_name', productName);
    formData.append('product_price', price);
    formData.append('quantity', 1);
    formData.append('product_image', imageFile || fallbackEmoji);

    fetch(window.APP_ROOT + '/Cart/add', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            // Get the response text first to see what's being returned
            return response.text().then(text => {
                console.log('Raw response text:', text);

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('❌ Content-Type is not JSON:', contentType);
                    throw new Error('Server returned non-JSON response: ' + contentType);
                }

                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Failed to parse JSON:', e);
                    console.error('Text was:', text);
                    throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                showNotification(data.message, 'success');
                updateCartBadge(data.cartItemCount);
            } else {
                showNotification(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            showNotification('An error occurred: ' + error.message, 'error');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
}

function updateCartBadge(count) {
    if (count !== undefined) {
        const badges = document.querySelectorAll('.cart-badge');
        badges.forEach(badge => {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        });
        return;
    }

    fetch(window.APP_ROOT + '/Cart/getData', {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badges = document.querySelectorAll('.cart-badge');
                badges.forEach(badge => {
                    badge.textContent = data.cartItemCount;
                    badge.style.display = data.cartItemCount > 0 ? 'inline-block' : 'none';
                });
            }
        })
        .catch(error => {
            console.error('Error fetching cart data:', error);
        });
}

function buyNow(productId, productName, price, maxQuantity) {
    const btn = event?.target;
    const originalText = btn?.textContent;
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
    }

    const productCard = document.querySelector(`.product-card[data-id="${productId}"]`);
    let imageFile = '';
    if (productCard) {
        imageFile = productCard.getAttribute('data-image') || '';
        if (!imageFile) {
            const imgEl = productCard.querySelector('.product-image img');
            const src = imgEl?.getAttribute('src') || '';
            if (src && !/default-product\.svg$/i.test(src)) {
                try {
                    imageFile = src.split('/').pop();
                } catch (e) {
                    imageFile = '';
                }
            }
        }
    }

    const fallbackEmoji = productCard?.querySelector('.product-placeholder')?.textContent || '🌱';

    fetch(window.APP_ROOT + '/Cart/clear', {
        method: 'POST',
        credentials: 'include'
    })
        .then(resp => resp.json())
        .then(clearData => {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('product_name', productName);
            formData.append('product_price', price);
            formData.append('quantity', 1);
            formData.append('product_image', imageFile || fallbackEmoji);
            formData.append('buy_now', '1');

            return fetch(window.APP_ROOT + '/Cart/add', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
        })
        .then(resp => resp.json())
        .then(data => {
            if (data && data.success) {
                updateCartBadge(data.cartItemCount);
                window.location.href = window.APP_ROOT + '/Checkout?buy_now=1&product_id=' + productId;
            } else {
                showNotification(data.message || 'Failed to proceed to checkout', 'error');
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            }
        })
        .catch(err => {
            console.error('Buy Now error:', err);
            showNotification('An error occurred while processing Buy Now', 'error');
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
}

function showLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function updateQuantity(productId, newQuantity) {
    if (newQuantity <= 0) {
        removeFromCart(productId);
        return;
    }

    showLoading();

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', newQuantity);

    fetch(window.APP_ROOT + '/Cart/update', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            console.log('Update response:', data);
            hideLoading();

            if (data.success) {
                showNotification(data.message, 'success');

                const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                if (cartItem) {
                    const quantityDisplay = cartItem.querySelector('.quantity-display');
                    if (quantityDisplay) {
                        quantityDisplay.textContent = newQuantity;
                    }

                    const priceElement = cartItem.querySelector('.cart-item-total-price');
                    const unitPriceText = cartItem.querySelector('.cart-item-unit-price')?.textContent || '0';
                    const unitPrice = parseFloat(unitPriceText.replace(/[^\d.]/g, '')) || 0;
                    if (priceElement) {
                        priceElement.textContent = 'Rs. ' + (unitPrice * newQuantity).toFixed(2);
                    }

                    updateCartBadge(data.cartItemCount);
                    recalculateCartTotal();
                }
            } else {
                showNotification(data.message || 'Failed to update cart', 'error');
            }
        })
        .catch(error => {
            console.error('Update error:', error);
            hideLoading();
            showNotification('An error occurred: ' + error.message, 'error');
        });
}

function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    showLoading();

    const formData = new FormData();
    formData.append('product_id', productId);

    fetch(window.APP_ROOT + '/Cart/remove', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            console.log('Remove response:', data);
            hideLoading();

            if (data.success) {
                showNotification(data.message, 'success');

                const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
                if (cartItem) {
                    cartItem.style.transition = 'all 0.3s ease';
                    cartItem.style.opacity = '0';
                    cartItem.style.transform = 'translateX(-100px)';

                    setTimeout(() => {
                        cartItem.remove();
                        updateCartBadge(data.cartItemCount);
                        recalculateCartTotal();

                        const remainingItems = document.querySelectorAll('.cart-item');
                        if (remainingItems.length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
            } else {
                showNotification(data.message || 'Failed to remove item', 'error');
            }
        })
        .catch(error => {
            console.error('Remove error:', error);
            hideLoading();
            showNotification('An error occurred: ' + error.message, 'error');
        });
}

function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }

    showLoading();

    fetch(window.APP_ROOT + '/Cart/clear', {
        method: 'POST',
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('An error occurred while clearing cart', 'error');
            console.error('Error:', error);
        });
}

function recalculateCartTotal() {
    let total = 0;
    let itemCount = 0;

    document.querySelectorAll('.cart-item').forEach(item => {
        const priceText = item.querySelector('.cart-item-total-price')?.textContent || '0';
        const price = parseFloat(priceText.replace(/[^\d.]/g, '')) || 0;
        const quantity = parseInt(item.querySelector('.quantity-display')?.textContent) || 0;

        total += price;
        itemCount += quantity;
    });

    const summaryValue = document.querySelector('.cart-summary-value');
    if (summaryValue) {
        summaryValue.textContent = itemCount;
    }

    const subtotal = document.querySelectorAll('.cart-summary-row .cart-summary-value')[1];
    if (subtotal) {
        subtotal.textContent = 'Rs. ' + total.toFixed(2);
    }

    const totalAmount = document.querySelector('.cart-summary-total-amount');
    if (totalAmount) {
        totalAmount.textContent = 'Rs. ' + total.toFixed(2);
    }
}

function proceedToCheckout() {
    window.location.href = window.APP_ROOT + '/Checkout?cart=1';
}

function addToWishlist(productId, evt) {
    console.log('🎯 addToWishlist called with productId:', productId, 'event:', evt);

    // Get button element - handle event object passed as second parameter
    let btn = null;
    if (evt && typeof evt === 'object') {
        btn = evt.currentTarget || evt.target;
        console.log('✓ Button found from event:', btn);
    }

    // Fallback: find button by product ID if not found in event
    if (!btn) {
        btn = document.querySelector(`.product-card[data-id="${productId}"] button:contains("Wishlist")`) ||
            document.querySelector(`.product-card[data-id="${productId}"] .btn-outline`);
        console.log('✓ Button found by fallback selector:', btn);
    }

    const originalText = btn ? btn.textContent : null;
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Adding...';
        console.log('✓ Button disabled and text changed to "Adding..."');
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    console.log('📤 Sending POST to:', window.APP_ROOT + '/Wishlist/add');

    fetch(window.APP_ROOT + '/Wishlist/add', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => {
            console.log('📥 Response received:', response.status, response.statusText);
            const contentType = response.headers.get('content-type');
            console.log('📋 Content-Type:', contentType);
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Wishlist add response data:', data);
            if (data.success) {
                showNotification(data.message || 'Added to wishlist', 'success');
                // Update button appearance
                if (btn) {
                    btn.style.opacity = '0.6';
                    btn.style.pointerEvents = 'none';
                    console.log('✓ Button appearance updated');
                }
                // Reload wishlist if visible
                const wishlistSection = document.getElementById('wishlist-list');
                if (wishlistSection && wishlistSection.style.display !== 'none') {
                    console.log('🔄 Wishlist section visible, reloading...');
                    loadWishlist();
                } else {
                    console.log('⚠️ Wishlist section not visible or not found');
                }
            } else {
                console.error('❌ Wishlist add failed:', data.error);
                showNotification(data.error || 'Failed to add to wishlist', 'error');
            }
        })
        .catch(error => {
            console.error('❌ Wishlist add error:', error);
            showNotification('An error occurred while adding to wishlist', 'error');
        })
        .finally(() => {
            if (btn) {
                btn.disabled = false;
                btn.textContent = originalText;
                console.log('✓ Button re-enabled');
            }
        });
}

function removeFromWishlist(productId) {
    console.log('🗑️ removeFromWishlist called with productId:', productId);

    if (!confirm('Remove this product from your wishlist?')) {
        console.log('❌ User cancelled wishlist removal');
        return;
    }

    console.log('📤 Sending POST to:', window.APP_ROOT + '/Wishlist/remove/' + productId);

    fetch(window.APP_ROOT + '/Wishlist/remove/' + productId, {
        method: 'POST',
        credentials: 'include'
    })
        .then(response => {
            console.log('📥 Response received:', response.status, response.statusText);
            const contentType = response.headers.get('content-type');
            console.log('📋 Content-Type:', contentType);
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Wishlist remove response data:', data);
            if (data.success) {
                showNotification(data.message || 'Removed from wishlist', 'success');
                loadWishlist();
            } else {
                console.error('❌ Wishlist remove failed:', data.error);
                showNotification(data.error || 'Failed to remove product', 'error');
            }
        })
        .catch(error => {
            console.error('❌ Wishlist remove error:', error);
            showNotification('An error occurred while removing product', 'error');
        });
}

function loadWishlist() {
    console.log('📚 loadWishlist() called');
    console.log('🔍 Looking for wishlist-list container...');

    const container = document.getElementById('wishlist-list');
    console.log('Container found:', !!container, container);

    if (!container) {
        console.warn('⚠️ Wishlist container not found in DOM');
        return;
    }

    console.log('📤 Sending GET to:', window.APP_ROOT + '/Wishlist/get');

    fetch(window.APP_ROOT + '/Wishlist/get', {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => {
            console.log('📥 Response received:', response.status, response.statusText);
            const contentType = response.headers.get('content-type');
            console.log('📋 Content-Type:', contentType);
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Server returned non-JSON response');
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Wishlist data received:', data);
            if (data.success) {
                console.log('📦 Items count:', data.items ? data.items.length : 0);
                renderWishlist(data.items || []);
            } else {
                console.error('❌ Failed to load wishlist:', data.error);
                container.innerHTML = `
                <div class="buyer-wishlist-empty-state">
                    <div class="buyer-wishlist-empty-icon">⚠️</div>
                    <h3>${escapeHtml(data.error || 'Failed to load wishlist')}</h3>
                </div>
            `;
            }
        })
        .catch(error => {
            console.error('❌ Load wishlist error:', error);
            container.innerHTML = `
            <div class="buyer-wishlist-empty-state">
                <div class="buyer-wishlist-empty-icon">⚠️</div>
                <h3>Unable to load wishlist</h3>
                <p>${escapeHtml(error.message)}</p>
            </div>
        `;
        });
}

function renderWishlist(items) {
    console.log('🎨 renderWishlist() called with items:', items);
    console.log('📦 Items count:', items ? items.length : 0);

    const container = document.getElementById('wishlist-list');
    console.log('Container element:', container);

    if (!container) {
        console.error('❌ Container #wishlist-list not found in DOM');
        return;
    }

    if (!items || !items.length) {
        console.log('📭 Wishlist is empty, rendering empty state');
        container.innerHTML = `
            <div class="buyer-wishlist-empty-state">
                <div class="buyer-wishlist-empty-icon">❤️</div>
                <h3>Your wishlist is empty</h3>
                <p>Browse products and click "Wishlist" to save them here.</p>
            </div>
        `;
        return;
    }

    console.log('📦 Rendering ' + items.length + ' items...');

    const html = items.map((item, idx) => {
        console.log('  Item ' + idx + ':', item);
        const image = item.image
            ? `${window.APP_ROOT}/assets/images/products/${escapeHtml(item.image)}`
            : `${window.APP_ROOT}/assets/images/default-product.svg`;

        const price = (item.price !== null && item.price !== undefined)
            ? `Rs. ${Number(item.price).toFixed(2)}/kg`
            : 'Price unavailable';

        const stockRaw = (item.available_quantity !== null && item.available_quantity !== undefined && item.available_quantity !== '')
            ? item.available_quantity
            : (item.quantity !== null && item.quantity !== undefined ? item.quantity : 0);
        const stockNum = Number(stockRaw);
        const stockDisplay = Number.isFinite(stockNum)
            ? (Number.isInteger(stockNum) ? stockNum.toString() : stockNum.toFixed(2).replace(/\.?0+$/, ''))
            : String(stockRaw || 0);
        const stock = `${escapeHtml(stockDisplay)} kg available`;

        const isOutOfStock = !item.available_quantity || item.available_quantity <= 0;

        return `
            <div class="product-card" 
                 data-wishlist-product="${item.product_id}" 
                 data-id="${item.product_id}" 
                 data-name="${escapeHtml((item.name || 'Product').toLowerCase())}" 
                 data-image="${escapeHtml(item.image || '')}">
                <div class="product-image">
                    <img src="${image}" alt="${escapeHtml(item.name || 'Product')}" class="${item.image ? '' : 'buyer-wishlist-fallback-image'}">
                </div>
                <div class="product-info">
                    <h3 class="product-name">${escapeHtml(item.name || 'Product unavailable')}</h3>
                    <div class="product-price">${price}</div>
                    <div class="product-stock">${stock}</div>
                    <div class="buyer-wishlist-actions">
                        <button class="btn btn-primary buyer-wishlist-btn"
                            onclick='BuyerDashboard.addToCart(${item.product_id}, ${JSON.stringify(item.name || "Product")}, ${item.price || 0}, ${item.available_quantity || 0})'>
                            Add to Cart
                        </button>
                        <button class="btn btn-danger buyer-wishlist-btn" onclick="BuyerDashboard.removeFromWishlist(${item.product_id})">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    console.log('✅ HTML generated, length: ' + html.length);
    container.innerHTML = html;
    console.log('✅ Container HTML updated with wishlist items');
}

function escapeHtml(text = '') {
    // Check if text is null, undefined, or not a string
    if (!text || typeof text !== 'string') {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

window.BuyerDashboard = {
    showSection,
    filterProducts,
    addToCart,
    buyNow,
    updateCartBadge,
    showLoading,
    hideLoading,
    updateQuantity,
    removeFromCart,
    clearCart,
    recalculateCartTotal,
    proceedToCheckout,
    loadProfileData,
    updateProfile,
    uploadPhoto,
    addToWishlist,
    removeFromWishlist,
    loadWishlist
};

// Backward-compatible aliases (temporary)
window.showSection = window.BuyerDashboard.showSection;
window.filterProducts = window.BuyerDashboard.filterProducts;
window.addToCart = window.BuyerDashboard.addToCart;
window.updateCartBadge = window.BuyerDashboard.updateCartBadge;
window.showLoading = window.BuyerDashboard.showLoading;
window.hideLoading = window.BuyerDashboard.hideLoading;
window.updateQuantity = window.BuyerDashboard.updateQuantity;
window.removeFromCart = window.BuyerDashboard.removeFromCart;
window.clearCart = window.BuyerDashboard.clearCart;
window.recalculateCartTotal = window.BuyerDashboard.recalculateCartTotal;
window.proceedToCheckout = window.BuyerDashboard.proceedToCheckout;
window.loadProfileData = window.BuyerDashboard.loadProfileData;
window.updateProfile = window.BuyerDashboard.updateProfile;
window.uploadPhoto = window.BuyerDashboard.uploadPhoto;
window.addToWishlist = window.BuyerDashboard.addToWishlist;
window.removeFromWishlist = window.BuyerDashboard.removeFromWishlist;
window.loadWishlist = window.BuyerDashboard.loadWishlist;

// Order management functions
function viewOrderDetails(orderId) {
    const modal = document.getElementById('order-details-modal');
    const modalBody = document.getElementById('modal-body');

    if (modal) {
        modal.style.display = 'block';
        // Reset content to loading state
        modalBody.innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <div class="loader" style="border: 4px solid #f3f3f3; border-top: 4px solid #4CAF50; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="margin-top: 16px; color: #666;">Loading order details...</p>
            </div>
        `;
    }

    fetch(window.APP_ROOT + '/BuyerOrders/details?id=' + orderId, {
        method: 'GET',
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                const items = data.items;
                const orderDate = new Date(order.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

                let itemsHtml = '';
                items.forEach(item => {
                    const itemTotal = parseFloat(item.product_price) * parseInt(item.quantity);
                    const pickupAddress = escapeHtml(item.product_full_address || '');
                    itemsHtml += `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #eee;">
                        <div style="flex: 1;">
                            <div style="font-weight: 500;">${escapeHtml(item.product_name)}</div>
                            <div style="font-size: 0.85rem; color: #666;">${item.quantity} kg x Rs. ${parseFloat(item.product_price).toFixed(2)}</div>
                            ${item.farmer_name ? `<div style="font-size: 0.8rem; color: #888;">Farmer: ${escapeHtml(item.farmer_name)}</div>` : ''}
                            ${pickupAddress ? `<div style="font-size: 0.8rem; color: #888;">Pickup: ${pickupAddress}</div>` : ''}
                        </div>
                        <div style="font-weight: 500;">Rs. ${itemTotal.toFixed(2)}</div>
                    </div>
                `;
                });

                modalBody.innerHTML = `
                <div style="border-bottom: 1px solid #eee; padding-bottom: 16px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h2 style="margin: 0; color: #2c3e50;">Order #ORD-${order.id}</h2>
                        <span class="order-status buyer-order-modal-status ${order.status.toLowerCase()}">${order.status}</span>
                    </div>
                    <p style="margin: 0; color: #666;">Placed on ${orderDate}</p>
                </div>

                <div style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 12px; color: #2c3e50;">Items</h4>
                    ${itemsHtml}
                </div>

                <div style="background: #f8f9fa; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #666;">Subtotal</span>
                        <span>Rs. ${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="color: #666;">Shipping</span>
                        <span>Rs. ${parseFloat(order.shipping_cost).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #dee2e6; padding-top: 8px; margin-top: 8px; font-weight: bold; font-size: 1.1rem;">
                        <span>Total</span>
                        <span style="color: #4CAF50;">Rs. ${parseFloat(order.order_total).toFixed(2)}</span>
                    </div>
                </div>

                <div style="margin-top: 24px;">
                    <h4 style="margin-bottom: 12px; color: #2c3e50;">Delivery Details</h4>
                    <p style="margin: 0 0 4px 0;"><strong>Address:</strong> ${escapeHtml(order.delivery_address)}</p>
                    <p style="margin: 0 0 4px 0;"><strong>District:</strong> ${escapeHtml(order.district_name || order.delivery_city)}</p>
                    <p style="margin: 0 0 4px 0;"><strong>Phone:</strong> ${escapeHtml(order.delivery_phone)}</p>
                    <p style="margin: 0;"><strong>Payment Status:</strong> ${escapeHtml(order.payment_status || 'pending')}</p>
                </div>
            `;
            } else {
                modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                    <h3>Error</h3>
                    <p>${data.message || 'Failed to load order details'}</p>
                </div>
            `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (modalBody) {
                modalBody.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #dc3545;">
                    <h3>Error</h3>
                    <p>An error occurred while loading details</p>
                </div>
            `;
            }
        });
}

function closeOrderModal() {
    const modal = document.getElementById('order-details-modal');
    if (modal) {
        modal.style.display = 'none';

        // Also remove the hash if it was set to keep history clean (optional)
        // history.pushState("", document.title, window.location.pathname + window.location.search);
    }
}

// Close modal when clicking outside of it
window.addEventListener('click', function (event) {
    const modal = document.getElementById('order-details-modal');
    if (event.target == modal) {
        closeOrderModal();
    }
});

function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }

    const formData = new FormData();
    formData.append('order_id', orderId);

    fetch(window.APP_ROOT + '/BuyerOrders/cancel', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order cancelled successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showNotification(data.message || 'Failed to cancel order', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
}

function trackOrder(orderId) {
    window.location.href = window.APP_ROOT + '/buyertracking?order_id=' + orderId;
}

function reorderItems(orderId) {
    showNotification('Adding items to cart...', 'info');
    // TODO: Implement reorder functionality
    console.log('Reorder items from order:', orderId);
}

window.BuyerDashboard.viewOrderDetails = viewOrderDetails;
window.BuyerDashboard.cancelOrder = cancelOrder;
window.BuyerDashboard.trackOrder = trackOrder;
window.BuyerDashboard.reorderItems = reorderItems;
window.BuyerDashboard.closeOrderModal = closeOrderModal;

// Backward-compatible aliases (temporary)
window.viewOrderDetails = window.BuyerDashboard.viewOrderDetails;
window.cancelOrder = window.BuyerDashboard.cancelOrder;
window.trackOrder = window.BuyerDashboard.trackOrder;
window.reorderItems = window.BuyerDashboard.reorderItems;
window.closeOrderModal = window.BuyerDashboard.closeOrderModal;
