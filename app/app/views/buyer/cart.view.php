<div class="content-header">
                <h1 class="content-title">My Shopping Cart</h1>
                <p class="content-subtitle">Review items before checkout</p>
            </div>

            <div id="cartContainer">
                <?php if (empty($cartItems) || !is_array($cartItems)): ?>
                    <!-- Empty Cart -->
                    <div class="content-card cart-empty-state">
                        <div class="cart-empty-icon">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p class="cart-empty-text">Add some products to get started!</p>
                        <a href="<?= ROOT ?>/buyerDashboard#products" class="btn btn-primary cart-empty-browse-btn" onclick="window.location.href='<?= ROOT ?>/buyerDashboard'; setTimeout(() => document.querySelector('[data-section=products]').click(), 100);">Browse Products</a>
                    </div>
                <?php else: ?>
                    <!-- Cart Items Grid -->
                    <div class="cart-layout">
                        <!-- Cart Items -->
                        <div class="cart-items-container">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="content-card cart-item" data-product-id="<?= htmlspecialchars($item->product_id) ?>">
                                    <div class="cart-item-content">
                                        <!-- Product Image -->
                                        <div class="cart-item-image">
                                            <?php
                                            // Prefer cart-stored image, else fallback to product's image from join
                                            $imgFile = '';
                                            if (!empty($item->product_image) && strlen($item->product_image) > 2 && strpos($item->product_image, '.') !== false) {
                                                $imgFile = $item->product_image;
                                            } elseif (!empty($item->product_image_db)) {
                                                $imgFile = $item->product_image_db;
                                            }
                                            ?>
                                            <?php if (!empty($imgFile) && file_exists("assets/images/products/" . $imgFile)): ?>
                                                <img src="<?= ROOT ?>/assets/images/products/<?= htmlspecialchars($imgFile) ?>" alt="<?= htmlspecialchars($item->product_name) ?>" class="cart-item-image-thumb">
                                            <?php else: ?>
                                                <div class="cart-item-image-fallback">
                                                    <?= htmlspecialchars(!empty($item->product_image) && strlen($item->product_image) <= 8 ? $item->product_image : '🌱') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Product Info -->
                                        <div class="cart-item-info">
                                            <h3 class="cart-item-name"><?= htmlspecialchars($item->product_name) ?></h3>
                                            <p class="cart-item-farmer">
                                                <?= htmlspecialchars($item->farmer_name) ?>
                                                <?php if (!empty($item->farmer_location)): ?>
                                                    (<?= htmlspecialchars($item->farmer_location) ?>)
                                                <?php endif; ?>
                                            </p>

                                            <div class="cart-item-pricing">
                                                <!-- Price -->
                                                <div>
                                                    <div class="cart-item-unit-price">Rs. <?= number_format($item->product_price, 2) ?>/kg</div>
                                                    <div class="cart-item-total-price">
                                                        Rs. <?= number_format($item->product_price * $item->quantity, 2) ?>
                                                    </div>
                                                </div>

                                                <!-- Quantity Controls -->
                                                <div class="quantity-controls">
                                                    <button class="quantity-btn quantity-decrease"
                                                        onclick="updateCartQuantity('<?= $item->product_id ?>', -1)">
                                                        -
                                                    </button>
                                                    <span class="quantity-display"><?= $item->quantity ?></span>
                                                    <button class="quantity-btn quantity-increase"
                                                        onclick="updateCartQuantity('<?= $item->product_id ?>', 1)">
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Remove Button -->
                                        <div class="cart-item-remove">
                                            <button class="btn btn-danger btn-sm"
                                                onclick="removeFromCart('<?= $item->product_id ?>')">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Cart Summary - Sticky -->
                        <div class="cart-summary-sticky">
                            <div class="cart-summary">
                                <h3 class="cart-summary-title">Cart Summary</h3>

                                <div class="cart-summary-items">
                                    <div class="cart-summary-row">
                                        <span>Items:</span>
                                        <span class="cart-summary-value"><?= $cartItemCount ?></span>
                                    </div>
                                    <div class="cart-summary-row">
                                        <span>Subtotal:</span>
                                        <span class="cart-summary-value">Rs. <?= number_format($cartTotal, 2) ?></span>
                                    </div>
                                    <div class="cart-summary-row cart-summary-delivery">
                                        <span>Delivery:</span>
                                        <span>TBD at checkout</span>
                                    </div>
                                </div>

                                <div class="cart-summary-total">
                                    <span class="cart-summary-total-label">Total:</span>
                                    <span class="cart-summary-total-amount">Rs. <?= number_format($cartTotal, 2) ?></span>
                                </div>

                                <div class="cart-summary-actions">
                                    <button class="btn btn-primary btn-large btn-checkout" onclick="proceedToCheckout()">
                                        Proceed to Checkout
                                    </button>
                                    <a href="<?= ROOT ?>/buyerProducts"
                                        class="btn btn-outline btn-large btn-continue">
                                        Continue Shopping
                                    </a>
                                    <button class="btn btn-danger btn-large btn-clear-cart" onclick="clearCart()">
                                        Clear Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-text">Processing...</div>
            <div class="loading-spinner"></div>
        </div>
    </div>

    <script src="<?= ROOT ?>/assets/js/systemDialog.js"></script>
    <script>
        window.APP_ROOT = "<?= ROOT ?>";

        function proceedToCheckout() {
            window.location.href = '<?= ROOT ?>/checkout?cart=1';
        }

        function updateCartQuantity(productId, change) {
            const formData = new FormData();
            formData.append('product_id', productId);
            
            // Get current quantity
            const quantityDisplay = document.querySelector(`[data-product-id="${productId}"] .quantity-display`);
            const currentQuantity = parseInt(quantityDisplay.textContent) || 1;
            const newQuantity = Math.max(1, currentQuantity + change);
            
            formData.append('quantity', newQuantity);
            
            fetch(window.APP_ROOT + '/Cart/update', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    showNotification(data.message || 'Failed to update cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        async function removeFromCart(productId) {
            const ok = await systemConfirm('Are you sure you want to remove this item from your cart?', 'Remove Item');
            if (!ok) return;
            
            const formData = new FormData();
            formData.append('product_id', productId);
            
            fetch(window.APP_ROOT + '/Cart/remove', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    showNotification(data.message || 'Failed to remove item', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        async function clearCart() {
            const ok = await systemConfirm('Are you sure you want to clear your entire cart?', 'Clear Cart');
            if (!ok) return;
            
            fetch(window.APP_ROOT + '/Cart/clear', {
                method: 'POST',
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    showNotification(data.message || 'Failed to clear cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            });
        }

        // Export functions to window
        window.proceedToCheckout = proceedToCheckout;
        window.updateCartQuantity = updateCartQuantity;
        window.removeFromCart = removeFromCart;
        window.clearCart = clearCart;
    </script>
