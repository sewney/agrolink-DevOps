<div class="content-section farmer-products-page">
    <div class="content-header">
        <h1 class="content-title">My Products</h1>
        <button class="btn btn-add-product" data-modal="addProductModal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Add New Product
        </button>
    </div>
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price/KG</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Listed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <!-- Products will be populated here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Add New Product</h3>
        </div>
        <div class="modal-body">
            <form id="addProductForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="productCategory">Category *</label>
                    <select id="productCategory" name="category" class="form-control" required>
                        <option value="">Select Category...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="productMaster">Standard Crop (Optional)</label>
                    <select id="productMaster" name="product_master_id" class="form-control" disabled>
                        <option value="">Select Category First</option>
                    </select>
                    <span class="form-hint">Choose from the crop list to auto-fill the product name, or type your own name below.</span>
                </div>

                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="name" class="form-control" placeholder="e.g., Fresh Pumpkin" required>
                </div>

                <div class="product-form-row">
                    <div class="form-group">
                        <label for="productPrice">Price per KG (Rs.) *</label>
                        <input type="number" id="productPrice" name="price" class="form-control" step="0.01" min="0" required placeholder="120.00">
                    </div>
                    <div class="form-group">
                        <label for="productQuantity">Available Quantity (KG) *</label>
                        <input type="number" id="productQuantity" name="quantity" class="form-control" min="1" required placeholder="100">
                    </div>
                </div>

                <div class="product-form-row">
                    <div class="form-group">
                        <label class="form-label">District</label>
                        <select class="form-control" id="productDistrict" name="district_id" required>
                            <option value="">Select District...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Town</label>
                        <select class="form-control" id="productTown" name="town_id" required disabled>
                            <option value="">Select District First</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="productLocation" name="location" value="auto">

                <div class="form-group">
                    <label for="productFullAddress">Product Full Address *</label>
                    <textarea id="productFullAddress" name="full_address" class="form-control" rows="2" placeholder="e.g., No 12, Lake Road, Boralesgamuwa" required></textarea>
                    <span class="form-hint">This pickup address will be used in delivery details.</span>
                </div>

                <div class="form-group">
                    <label for="listingDate">Available From *</label>
                    <input type="date" id="listingDate" name="listing_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="productImage">Product Image *</label>
                    <input type="file" id="productImage" name="image" class="form-control" accept="image/*" required>
                    <span class="form-hint">Please upload a clear image of your product (Max 5MB, JPG/PNG/GIF/WebP)</span>
                    <div id="imagePreview" style="margin-top: 12px; display: none;">
                        <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #E8F5E9;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add Product</button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Edit Product</h3>
        </div>
        <div class="modal-body">
            <form id="editProductForm">
                <input type="hidden" id="editProductId">

                <div class="form-group">
                    <label for="editProductCategory">Category *</label>
                    <select id="editProductCategory" name="category" class="form-control" required>
                        <option value="">Select Category...</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editProductMaster">Standard Crop (Optional)</label>
                    <select id="editProductMaster" name="product_master_id" class="form-control" disabled>
                        <option value="">Select Category First</option>
                    </select>
                    <span class="form-hint">Optional: Choose from standard crops, or edit the custom name below.</span>
                </div>

                <div class="form-group">
                    <label for="editProductName">Product Name *</label>
                    <input type="text" id="editProductName" name="name" class="form-control" placeholder="e.g., Fresh Pumpkin" required>
                </div>

                <div class="product-form-row">
                    <div class="form-group">
                        <label for="editProductPrice">Price per KG (Rs.) *</label>
                        <input type="number" id="editProductPrice" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="editProductQuantity">Available Quantity (KG) *</label>
                        <input type="number" id="editProductQuantity" name="quantity" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="product-form-row">
                    <div class="form-group">
                        <label class="form-label">District</label>
                        <select class="form-control" id="editProductDistrict" name="district_id" required>
                            <option value="">Select District...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Town</label>
                        <select class="form-control" id="editProductTown" name="town_id" required disabled>
                            <option value="">Select District First</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" id="editProductLocation" name="location" value="">

                <div class="form-group">
                    <label for="editProductFullAddress">Product Full Address *</label>
                    <textarea id="editProductFullAddress" name="full_address" class="form-control" rows="2" required></textarea>
                    <span class="form-hint">Used as pickup address in delivery request and order delivery details.</span>
                </div>

                <div class="form-group">
                    <label for="editListingDate">Available From *</label>
                    <input type="date" id="editListingDate" name="listing_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="editProductImage">Replace Image (optional)</label>
                    <input type="file" id="editProductImage" name="image" class="form-control" accept="image/*">
                    <span class="form-hint">Leave empty to keep current image. Max 5MB, JPG/PNG/GIF/WebP</span>
                    <div id="editImagePreview" style="margin-top: 12px; display: none;">
                        <img id="editPreviewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #E8F5E9;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>