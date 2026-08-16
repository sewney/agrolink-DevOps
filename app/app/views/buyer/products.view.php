<!-- Products Section - Content view rendered inside buyerLayout -->
<div class="content-header">
    <h1 class="content-title">Browse Products</h1>
    <p class="content-subtitle">Discover fresh produce from local farmers</p>
</div>

<!-- Filter Section -->
<div class="content-card">
    <div class="card-header">
        <h3 class="card-title">Filter Products</h3>
    </div>
    <div class="card-content">
        <div class="buyer-products-filter-grid">
            <div>
                <label for="searchInput" class="buyer-products-hidden-label">Search Products</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or farmer..." onkeyup="BuyerDashboard.filterProducts()" aria-label="Search products">
            </div>
            <div>
                <label for="categoryFilter" class="buyer-products-hidden-label">Filter by Category</label>
                <select id="categoryFilter" class="form-control" onchange="BuyerDashboard.filterProducts()" aria-label="Filter by category">
                    <option value="">All Categories</option>
                    <option value="vegetables">Vegetables</option>
                    <option value="fruits">Fruits</option>
                    <option value="spices">Spices</option>
                </select>
            </div>
            <div>
                <label for="locationFilter" class="buyer-products-hidden-label">Filter by Location</label>
                <select id="locationFilter" class="form-control" onchange="BuyerDashboard.filterProducts()" aria-label="Filter by location">
                    <option value="">All Locations</option>
                    <option value="colombo">Colombo</option>
                    <option value="kandy">Kandy</option>
                    <option value="matale">Matale</option>
                    <option value="anuradhapura">Anuradhapura</option>
                    <option value="galle">Galle</option>
                    <option value="nuwara eliya">Nuwara Eliya</option>
                    <option value="badulla">Badulla</option>
                    <option value="kurunegala">Kurunegala</option>
                    <option value="gampaha">Gampaha</option>
                    <option value="jaffna">Jaffna</option>
                    <option value="trincomalee">Trincomalee</option>
                    <option value="batticaloa">Batticaloa</option>
                    <option value="ampara">Ampara</option>
                    <option value="polonnaruwa">Polonnaruwa</option>
                    <option value="puttalam">Puttalam</option>
                    <option value="ratnapura">Rathnapura</option>
                    <option value="kilinochchi">Kilinochchi</option>
                    <option value="mullaitivu">Mullaitivu</option>
                    <option value="mannar">Mannar</option>
                    <option value="matara">Matara</option>
                    <option value="kegalle">Kegalle</option>
                    <option value="kalutara">Kalutara</option>
                    <option value="hambanthota">Hambanthota</option>
                    <option value="monaragala">Monaragala</option>
                    <option value="vavuniya">Vavuniya</option>
                </select>
            </div>
            <div>
                <label for="priceFilter" class="buyer-products-hidden-label">Filter by Price</label>
                <select id="priceFilter" class="form-control" onchange="BuyerDashboard.filterProducts()" aria-label="Filter by price">
                    <option value="">All Prices</option>
                    <option value="0-100">Under Rs. 100</option>
                    <option value="100-200">Rs. 100 - Rs. 200</option>
                    <option value="200-500">Rs. 200 - Rs. 500</option>
                    <option value="500+">Above Rs. 500</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="products-grid" id="productsGrid">
    <?php if (empty($products)): ?>
        <div class="buyer-products-empty-state">
            <div class="buyer-products-empty-icon">🌾</div>
            <h3>No products available yet</h3>
            <p>Check back later for fresh products from our farmers!</p>
        </div>
    <?php else: ?>
        <?php foreach ($products as $product): ?>
            <div class="product-card"
                data-id="<?= htmlspecialchars($product->id) ?>"
                data-name="<?= strtolower(htmlspecialchars($product->name)) ?>"
                data-category="<?= strtolower(htmlspecialchars($product->category)) ?>"
                data-location="<?= strtolower(htmlspecialchars($product->location)) ?>"
                data-price="<?= htmlspecialchars($product->price) ?>"
                data-farmer="<?= strtolower(htmlspecialchars($product->farmer_name ?? '')) ?>"
                data-image="<?= !empty($product->image) ? htmlspecialchars($product->image) : '' ?>">

                <div class="product-image">
                    <?php if (!empty($product->image) && file_exists("assets/images/products/" . $product->image)): ?>
                        <img src="<?= ROOT ?>/assets/images/products/<?= htmlspecialchars($product->image) ?>"
                            alt="<?= htmlspecialchars($product->name) ?>">
                    <?php else: ?>
                        <img src="<?= ROOT ?>/assets/images/default-product.svg"
                            alt="<?= htmlspecialchars($product->name) ?>"
                            class="buyer-products-fallback-image">
                    <?php endif; ?>
                </div>

                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($product->name) ?></h3>
                    <p class="product-farmer">
                        <?= htmlspecialchars($product->farmer_name ?? 'Unknown Farmer') ?>
                    </p>
                    <p class="product-description buyer-products-location-line">
                        Location: <?= htmlspecialchars($product->farmer_district ?? $product->location ?? 'Unknown Location') ?>
                    </p>
                    <p class="product-description">
                        <?= htmlspecialchars($product->description ?? 'Fresh produce from local farm') ?>
                    </p>
                    <div class="buyer-products-date-meta">
                        <div>Added: <?= htmlspecialchars($product->display_added_date ?? '-') ?></div>
                        <div>Best use before: <?= htmlspecialchars($product->display_best_use_date ?? '-') ?></div>
                    </div>
                    <div class="product-price">Rs. <?= number_format($product->price, 2) ?>/kg</div>
                    <div class="product-stock">
                        <?= htmlspecialchars($product->quantity) ?>kg available
                    </div>
                    <div class="buyer-products-actions">
                        <button class="btn btn-primary btn-buy-now buyer-product-action-btn"
                            onclick="BuyerDashboard.buyNow(<?= $product->id ?>, '<?= addslashes(htmlspecialchars($product->name)) ?>', <?= $product->price ?>, <?= $product->quantity ?>)">
                            Buy Now
                        </button>

                        <div class="buyer-products-secondary-actions">
                            <button class="btn btn-primary btn-add-cart buyer-product-action-btn"
                                onclick="BuyerDashboard.addToCart(<?= $product->id ?>, '<?= addslashes(htmlspecialchars($product->name)) ?>', <?= $product->price ?>, <?= $product->quantity ?>)">
                                Add to Cart
                            </button>
                            <button class="btn btn-outline buyer-product-action-btn" onclick="BuyerDashboard.addToWishlist(<?= $product->id ?>, event)">
                                Wishlist
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>