<!-- Wishlist Section - Content view rendered inside buyerLayout -->
<div class="content-header">
    <h1 class="content-title">My Wishlist</h1>
    <p class="content-subtitle">Products you plan to purchase later</p>
</div>

<div class="products-grid" id="wishlist-list">
    <?php if (empty($wishlistItems)): ?>
        <div class="buyer-wishlist-empty-state">
            <div class="buyer-wishlist-empty-icon"></div>
            <h3>Your wishlist is empty</h3>
            <p>Browse products and click "Wishlist" to save them here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($wishlistItems as $item): ?>
            <?php
            $stockQty = $item->available_quantity ?? $item->quantity ?? 0;
            $stockQtyDisplay = is_numeric($stockQty) ? rtrim(rtrim(number_format((float)$stockQty, 2, '.', ''), '0'), '.') : (string)$stockQty;
            ?>
            <div class="product-card" 
                 data-wishlist-product="<?= (int)$item->product_id ?>"
                 data-id="<?= (int)$item->product_id ?>"
                 data-name="<?= strtolower(htmlspecialchars($item->name ?? 'Product')) ?>"
                 data-image="<?= !empty($item->image) ? htmlspecialchars($item->image) : '' ?>">
                <div class="product-image">
                    <?php if (!empty($item->image) && file_exists("assets/images/products/" . $item->image)): ?>
                        <img src="<?= ROOT ?>/assets/images/products/<?= htmlspecialchars($item->image) ?>"
                            alt="<?= htmlspecialchars($item->name) ?>">
                    <?php else: ?>
                        <img src="<?= ROOT ?>/assets/images/default-product.svg"
                            alt="<?= htmlspecialchars($item->name) ?>"
                            class="buyer-wishlist-fallback-image">
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($item->name ?? 'Product unavailable') ?></h3>
                    <div class="product-price">
                        <?= isset($item->price) ? 'Rs. ' . number_format($item->price, 2) . '/kg' : 'Price unavailable' ?>
                    </div>
                    <div class="product-stock">
                        <?= htmlspecialchars($stockQtyDisplay) ?> kg available
                    </div>
                    <div class="buyer-wishlist-actions">
                        <button class="btn btn-primary buyer-wishlist-btn"
                                onclick="BuyerDashboard.addToCart(<?= (int)$item->product_id ?>, '<?= addslashes(htmlspecialchars($item->name ?? 'Product')) ?>', <?= (float)($item->price ?? 0) ?>, <?= (float)($item->available_quantity ?? 0) ?>)">
                            Add to Cart
                        </button>
                        <button class="btn btn-danger buyer-wishlist-btn"
                                onclick="BuyerDashboard.removeFromWishlist(<?= (int)$item->product_id ?>)">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
