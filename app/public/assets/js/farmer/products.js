// Farmer Products Management

const API_BASE = (window.APP_ROOT || '') + '/farmerproducts';

// Escape HTML to prevent XSS
function escapeHtml(text = '') {
    if (!text || typeof text !== 'string') {
        return '';
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Location Management
function loadDistricts() {
    return fetch(`${API_BASE}/getDistricts`, { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.districts) {
                const options = '<option value="">Select District...</option>' +
                    res.districts.map(d => `<option value="${d.id}">${escapeHtml(d.district_name)}</option>`).join('');

                const addSelect = document.getElementById('productDistrict');
                if (addSelect) addSelect.innerHTML = options;

                const editSelect = document.getElementById('editProductDistrict');
                if (editSelect) editSelect.innerHTML = options;
            }
            return res;
        })
        .catch(err => {
            console.error(err);
            return { success: false };
        });
}

function loadTowns(districtId, townSelectId, selectedTownId = null) {
    const select = document.getElementById(townSelectId);
    if (!select) return;

    select.innerHTML = '<option value="">Loading...</option>';
    select.disabled = true;
    select.required = false;

    if (!districtId) {
        select.innerHTML = '<option value="">Select District First</option>';
        return;
    }

    fetch(`${API_BASE}/getTowns?district_id=${districtId}`, { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            const towns = Array.isArray(res.towns) ? res.towns : [];
            if (res.success && towns.length > 0) {
                select.innerHTML = '<option value="">Select Town...</option>' +
                    towns.map(t => `<option value="${t.id}">${escapeHtml(t.town_name)}</option>`).join('');
                select.disabled = false;
                select.required = true;

                if (selectedTownId) {
                    select.value = selectedTownId;
                }
            } else if (res.success && towns.length === 0) {
                // Some districts do not have towns configured; allow submit with district only.
                select.innerHTML = '<option value="">No towns available for this district</option>';
                select.disabled = true;
                select.required = false;
            } else {
                select.innerHTML = '<option value="">No towns found</option>';
                select.disabled = true;
                select.required = false;
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error loading towns</option>';
            select.disabled = true;
            select.required = false;
        });
}

// Product Category/Master Management
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function normalizeCategory(category) {
    return String(category || '').trim().toLowerCase();
}

function humanizeCategory(category) {
    const normalized = normalizeCategory(category);
    if (!normalized) {
        return 'Other';
    }

    return normalized
        .split(/[\s_-]+/)
        .filter(Boolean)
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function setSelectValueCaseInsensitive(selectEl, targetValue) {
    if (!selectEl) {
        return false;
    }

    const normalizedTarget = normalizeCategory(targetValue);
    if (!normalizedTarget) {
        return false;
    }

    for (const option of selectEl.options) {
        if (normalizeCategory(option.value) === normalizedTarget) {
            selectEl.value = option.value;
            return true;
        }
    }

    return false;
}

function loadCategories() {
    return fetch(`${API_BASE}/getCategories`, { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.categories) {
                const options = '<option value="">Select Category...</option>' +
                    res.categories.map(c => `<option value="${c}">${capitalize(c)}</option>`).join('');

                const addSelect = document.getElementById('productCategory');
                if (addSelect) addSelect.innerHTML = options;

                const editSelect = document.getElementById('editProductCategory');
                if (editSelect) editSelect.innerHTML = options;
            }
            return res;
        })
        .catch(err => {
            console.error(err);
            return { success: false };
        });
}

function loadProductsByCategory(category, productSelectId, selectedId = null) {
    const select = document.getElementById(productSelectId);
    if (!select) return;

    const normalizedCategory = normalizeCategory(category);

    select.innerHTML = '<option value="">Loading...</option>';
    select.disabled = true;

    if (!normalizedCategory) {
        select.innerHTML = '<option value="">Select Category First</option>';
        return;
    }

    fetch(`${API_BASE}/getProductsByCategory?category=${encodeURIComponent(normalizedCategory)}`, { credentials: 'include' })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.products) {
                select.innerHTML = '<option value="">Select Product...</option>' +
                    res.products.map(p =>
                        `<option value="${p.id}" data-name="${escapeHtml(p.crop_name)}">${escapeHtml(p.crop_name)}</option>`
                    ).join('');
                select.disabled = false;

                if (selectedId) {
                    select.value = selectedId;
                    // Trigger name update
                    const selectedOption = select.options[select.selectedIndex];
                    if (selectedOption && selectedOption.dataset.name) {
                        const nameField = productSelectId === 'productMaster' ?
                            document.getElementById('productName') :
                            document.getElementById('editProductName');
                        if (nameField) {
                            nameField.value = selectedOption.dataset.name;
                            nameField.readOnly = true;
                        }
                    }
                }
            } else {
                select.innerHTML = '<option value="">No products found</option>';
            }
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error loading products</option>';
        });
}

// Edit product form
let editProductBound = false;

function initializeEditForm() {
    const form = document.getElementById('editProductForm');
    if (!form || editProductBound) return;

    // basic numeric constraints
    const qty = document.getElementById('editProductQuantity');
    if (qty) { qty.setAttribute('min', '1'); qty.setAttribute('step', '1'); }

    // set min date to today
    const editDate = document.getElementById('editListingDate');
    if (editDate) {
        const today = new Date().toISOString().split('T')[0];
        editDate.setAttribute('min', today);
    }

    // image preview
    const imageInput = document.getElementById('editProductImage');
    const imagePreview = document.getElementById('editImagePreview');
    const previewImg = document.getElementById('editPreviewImg');
    if (imageInput && imagePreview && previewImg) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) { imagePreview.style.display = 'none'; return; }
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'error');
                imageInput.value = ''; imagePreview.style.display = 'none'; return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Image size must be less than 5MB', 'error');
                imageInput.value = ''; imagePreview.style.display = 'none'; return;
            }
            const reader = new FileReader();
            reader.onload = ev => { previewImg.src = ev.target.result; imagePreview.style.display = 'block'; };
            reader.readAsDataURL(file);
        });
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const id = document.getElementById('editProductId').value;
        const name = document.getElementById('editProductName').value.trim();
        const category = document.getElementById('editProductCategory').value;
        const price = document.getElementById('editProductPrice').value;
        const quantity = document.getElementById('editProductQuantity').value;
        const fullAddressInput = document.getElementById('editProductFullAddress');
        const fullAddress = fullAddressInput ? fullAddressInput.value.trim() : '';
        // const location = document.getElementById('editProductLocation').value.trim(); // Now hidden/auto
        const districtSelect = document.getElementById('editProductDistrict');
        const townSelect = document.getElementById('editProductTown');
        const districtId = districtSelect ? districtSelect.value : '';
        const townId = townSelect ? townSelect.value : '';
        const listing_date = document.getElementById('editListingDate').value;
        const requiresTown = !!(townSelect && !townSelect.disabled);

        if (!name || price === '' || quantity === '' || !districtId || !category || !listing_date || !fullAddress || (requiresTown && !townId)) {
            showNotification('Please fill all required fields', 'error');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const original = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Saving...';

        try {
            const fd = new FormData();
            fd.append('name', name);
            fd.append('category', category);
            fd.append('price', price);
            fd.append('quantity', quantity);
            fd.append('location', 'auto'); // Auto-generated in backend
            fd.append('full_address', fullAddress);
            fd.append('district_id', districtId);
            fd.append('town_id', townId);
            fd.append('listing_date', listing_date);
            const masterSelect = document.getElementById('editProductMaster');
            if (masterSelect && masterSelect.value) {
                fd.append('product_master_id', masterSelect.value);
            }
            if (imageInput && imageInput.files && imageInput.files[0]) {
                fd.append('image', imageInput.files[0]);
            }
            const r = await fetch(`${API_BASE}/update/${id}`, { method: 'POST', body: fd, credentials: 'include' });
            const res = await r.json();
            if (!r.ok || !res.success) {
                const msg = res?.error || 'Failed to update product';
                showNotification(msg, 'error');
                return;
            }
            showNotification('Product updated', 'success');
            closeModal('editProductModal');
            if (imageInput) { imageInput.value = ''; if (imagePreview) { imagePreview.style.display = 'none'; } }
            loadFarmerProducts();
        } catch (err) {
            showNotification('Failed to update product: ' + err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = original;
        }
    });

    // Clear errors on input
    form.querySelectorAll('.form-control').forEach(inp => inp.addEventListener('input', () => {
        inp.style.borderColor = ''; inp.style.background = '';
    }));

    editProductBound = true;
}

// Load products from backend
function loadFarmerProducts() {
    console.log('🔄 Loading farmer products from:', `${API_BASE}/farmerList`);
    fetch(`${API_BASE}/farmerList`, { credentials: 'include' })
        .then(r => {
            console.log('📥 Response status:', r.status);
            return r.json().then(data => ({ status: r.status, data }));
        })
        .then(({ status, data }) => {
            console.log('✅ Response data:', data);
            if (status === 200 && data.success) {
                console.log('📦 Products loaded:', data.products);
                populateProductsTable(data.products);
            } else {
                const error = data.error || 'Failed to load products';
                console.error('❌ Error:', error, 'Status:', status);
                showNotification(error, 'error');
            }
        })
        .catch(err => {
            console.error('❌ Fetch error:', err);
            showNotification('Failed to load products: ' + err.message, 'error');
        });
}

// Submit add product
let addProductBound = false;

function initializeProductForms() {
    const addProductForm = document.getElementById('addProductForm');
    if (!addProductForm || addProductBound) return;

    // Set minimum date to today
    const listingDateInput = document.getElementById('listingDate');
    if (listingDateInput) {
        const today = new Date().toISOString().split('T')[0];
        listingDateInput.setAttribute('min', today);
        listingDateInput.value = today;
    }

    // Set minimum quantity to 10kg
    const quantityInput = document.getElementById('productQuantity');
    if (quantityInput) {
        quantityInput.setAttribute('min', '10');
        quantityInput.setAttribute('step', '1');
    }

    // Image preview functionality
    const imageInput = document.getElementById('productImage');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    if (imageInput && imagePreview && previewImg) {
        imageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Please select a valid image file (JPG, PNG, GIF, or WebP)', 'error');
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    showNotification('Image size must be less than 5MB', 'error');
                    imageInput.value = '';
                    imagePreview.style.display = 'none';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImg.src = event.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);

                imageInput.style.borderColor = '';
                imageInput.style.background = '';
            } else {
                imagePreview.style.display = 'none';
            }
        });

        const closeBtns = document.querySelectorAll('#addProductModal [data-modal-close]');
        closeBtns.forEach(btn => btn.addEventListener('click', () => {
            imageInput.value = '';
            previewImg.src = '';
            imagePreview.style.display = 'none';
            imageInput.style.borderColor = '';
            imageInput.style.background = '';
        }));
    }

    addProductForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const imageInput = document.getElementById('productImage');
        if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
            showNotification('Product image is required. Please upload an image.', 'error');
            if (imageInput) {
                imageInput.style.borderColor = '#ef5350';
                imageInput.style.background = '#ffebee';
            }
            return;
        }

        const file = imageInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Please upload a valid image file (JPG, PNG, GIF, or WebP)', 'error');
            imageInput.style.borderColor = '#ef5350';
            imageInput.style.background = '#ffebee';
            return;
        }

        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showNotification('Image file size must be less than 5MB', 'error');
            imageInput.style.borderColor = '#ef5350';
            imageInput.style.background = '#ffebee';
            return;
        }

        const quantity = quantityInput ? parseInt(quantityInput.value) : 0;
        if (quantity < 10) {
            showNotification('Minimum quantity is 10kg', 'error');
            quantityInput.style.borderColor = '#ef5350';
            quantityInput.style.background = '#ffebee';
            return;
        }

        const fullAddressInput = document.getElementById('productFullAddress');
        if (fullAddressInput && fullAddressInput.value.trim().length < 8) {
            showNotification('Please enter a complete product full address', 'error');
            fullAddressInput.style.borderColor = '#ef5350';
            fullAddressInput.style.background = '#ffebee';
            return;
        }

        const fd = new FormData(addProductForm);
        const url = `${API_BASE}/create`;

        const submitBtn = addProductForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Adding...';

        try {
            const r = await fetch(url, {
                method: 'POST',
                body: fd,
                credentials: 'include'
            });

            const raw = await r.text();
            let res;

            try {
                res = JSON.parse(raw);
            } catch (parseError) {
                throw new Error(r.status + ' ' + r.statusText + ' (non-JSON response)');
            }

            if (!r.ok || !res.success) {
                if (res.errors) {
                    let errorMessages = [];

                    addProductForm.querySelectorAll('.form-control').forEach(input => {
                        input.style.borderColor = '';
                        input.style.background = '';
                    });

                    for (const [field, error] of Object.entries(res.errors)) {
                        errorMessages.push(error);

                        const fieldMap = {
                            'category': 'productCategory',
                            'name': 'productName',
                            'price': 'productPrice',
                            'quantity': 'productQuantity',
                            'location': 'productLocation',
                            'full_address': 'productFullAddress',
                            'listing_date': 'listingDate',
                            'image': 'productImage'
                        };

                        const inputId = fieldMap[field];
                        const input = document.getElementById(inputId);
                        if (input) {
                            input.style.borderColor = '#ef5350';
                            input.style.background = '#ffebee';
                        }
                    }

                    showNotification('Validation errors:\n' + errorMessages.join('\n'), 'error');
                } else {
                    throw new Error(res.error || ('HTTP ' + r.status));
                }
                return;
            }

            showNotification('Product added successfully', 'success');
            addProductForm.reset();
            const imgInput = document.getElementById('productImage');
            const imgPreviewWrap = document.getElementById('imagePreview');
            const imgPreview = document.getElementById('previewImg');
            if (imgInput) {
                imgInput.value = '';
                imgInput.style.borderColor = '';
                imgInput.style.background = '';
            }
            if (imgPreviewWrap) imgPreviewWrap.style.display = 'none';
            if (imgPreview) imgPreview.src = '';
            const productNameInput = document.getElementById('productName');
            if (productNameInput) {
                productNameInput.readOnly = false;
            }
            closeModal('addProductModal');

            if (listingDateInput) {
                const today = new Date().toISOString().split('T')[0];
                listingDateInput.value = today;
            }

            addProductForm.querySelectorAll('.form-control').forEach(input => {
                input.style.borderColor = '';
                input.style.background = '';
            });

            loadFarmerProducts();
        } catch (err) {
            showNotification('Failed to add product: ' + err.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    addProductForm.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('input', function () {
            this.style.borderColor = '';
            this.style.background = '';
        });
    });

    addProductBound = true;
}

// Populate table with database products
function populateProductsTable(products) {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px; color: #999;">No products listed yet</td></tr>';
        return;
    }

    products.forEach(p => {
        const row = document.createElement('tr');

        const listingDate = p.listing_date ? new Date(p.listing_date).toLocaleDateString() : '-';

        const categoryNames = {
            'vegetables': 'Vegetables', 'fruits': 'Fruits', 'cereals': 'Cereals',
            'yams': 'Yams', 'legumes': 'Legumes', 'spices': 'Spices',
            'leafy': 'Leafy', 'other': 'Other'
        };
        const categoryDisplay = categoryNames[normalizeCategory(p.category)] || humanizeCategory(p.category);

        row.innerHTML = `
            <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                    ${p.image ?
                `<img src="${window.APP_ROOT || ''}/assets/images/products/${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 2px solid #E8F5E9;">` :
                `<img src="${window.APP_ROOT || ''}/assets/images/default-product.svg" alt="${escapeHtml(p.name)}" style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover; border: 2px solid #E8F5E9; opacity: 0.5;">`
            }
                    <div style="font-weight: 600;">${escapeHtml(p.name)}</div>
                </div>
            </td>
            <td><span style="padding: 4px 10px; background: #E8F5E9; border-radius: 12px; font-size: 0.85rem; color: #2E7D32;">${categoryDisplay}</span></td>
            <td style="font-weight: 600;">Rs. ${Number(p.price).toFixed(2)}</td>
            <td>${p.quantity} kg</td>
            <td style="color: #555;">${escapeHtml(p.location) || '-'}</td>
            <td style="font-size: 0.9rem; color: #666;">${listingDate}</td>
            <td class="product-actions-cell">
                <div class="product-table-actions">
                    <button type="button" class="btn btn-sm product-action-btn product-action-edit" onclick="editProduct(${p.id})">
                        <svg class="product-action-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <span>Edit</span>
                    </button>
                    <button type="button" class="btn btn-sm product-action-btn product-action-delete" onclick="deleteProduct(${p.id})">
                        Delete
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Product Actions
async function editProduct(id) {
    try {
        const r = await fetch(`${API_BASE}/show/${id}`, { credentials: 'include' });
        const res = await r.json();
        if (!r.ok || !res.success || !res.product) {
            showNotification(res.error || 'Failed to load product', 'error');
            return;
        }
        const p = res.product;
        document.getElementById('editProductId').value = p.id;

        // Set Category and Product (must be done before populating other fields)
        const catSel = document.getElementById('editProductCategory');
        if (catSel && catSel.options.length <= 1) {
            await loadCategories();
        }
        if (catSel && p.category) {
            const categorySet = setSelectValueCaseInsensitive(catSel, p.category);
            if (!categorySet) {
                catSel.value = normalizeCategory(p.category);
            }
            // Always load category products so optional selector is usable in edit mode.
            loadProductsByCategory(catSel.value || normalizeCategory(p.category), 'editProductMaster', p.product_master_id || null);
        } else {
            const editMaster = document.getElementById('editProductMaster');
            if (editMaster) {
                editMaster.innerHTML = '<option value="">Select Category First</option>';
                editMaster.disabled = true;
            }
        }
        const editNameField = document.getElementById('editProductName');
        if (editNameField) {
            editNameField.value = p.name || '';
            editNameField.readOnly = !!p.product_master_id;
        }
        document.getElementById('editProductPrice').value = p.price ?? '';
        document.getElementById('editProductQuantity').value = p.quantity ?? '';
        const editFullAddress = document.getElementById('editProductFullAddress');
        if (editFullAddress) editFullAddress.value = p.full_address || '';
        // document.getElementById('editProductLocation').value = p.location || '';

        // Set District and Town
        const distSelect = document.getElementById('editProductDistrict');
        if (distSelect && distSelect.options.length <= 1) {
            await loadDistricts();
        }
        if (distSelect && p.district_id) {
            distSelect.value = p.district_id;
            loadTowns(p.district_id, 'editProductTown', p.town_id);
        } else {
            // Fallback if no district set? Reset town
            if (distSelect) distSelect.value = "";
            document.getElementById('editProductTown').innerHTML = '<option value="">Select District First</option>';
            document.getElementById('editProductTown').disabled = true;
        }

        const d = p.listing_date ? new Date(p.listing_date) : null;
        const iso = d && !isNaN(d) ? d.toISOString().split('T')[0] : '';
        const editDate = document.getElementById('editListingDate');
        if (editDate) editDate.value = iso;
        openModal('editProductModal');
    } catch (err) {
        showNotification('Failed to open edit form: ' + err.message, 'error');
    }
}

function prefillFromAcceptedCropRequest() {
    const params = new URLSearchParams(window.location.search);
    const requestId = params.get('from_request_id');
    if (!requestId) {
        return;
    }

    const cropName = params.get('crop_name') || '';
    const quantity = Number(params.get('quantity') || 0);
    const targetPrice = Number(params.get('target_price') || 0);
    const location = params.get('location') || '';

    const nameField = document.getElementById('productName');
    const qtyField = document.getElementById('productQuantity');
    const priceField = document.getElementById('productPrice');
    const fullAddressField = document.getElementById('productFullAddress');
    const locationField = document.getElementById('productLocation');
    const dateField = document.getElementById('listingDate');

    if (nameField && cropName) {
        nameField.value = cropName;
    }
    if (qtyField && quantity > 0) {
        qtyField.value = Math.max(quantity, 10);
    }
    if (priceField && targetPrice > 0) {
        priceField.value = targetPrice;
    }
    if (locationField && location) {
        locationField.value = location;
    }
    if (fullAddressField && location) {
        fullAddressField.value = location;
    }
    if (dateField) {
        dateField.value = new Date().toISOString().split('T')[0];
    }

    openModal('addProductModal');
    showNotification('Crop request data loaded. Select category and save product.', 'info');

    if (cropName) {
        autoSelectCategoryForCrop(cropName);
    }
}

// Try to auto-select category/product based on crop name from crop request
async function autoSelectCategoryForCrop(cropName) {
    try {
        const r = await fetch(`${API_BASE}/getCategories`, { credentials: 'include' });
        const data = await r.json();
        if (!data.success || !Array.isArray(data.categories)) return;

        const target = cropName.trim().toLowerCase();
        if (!target) return;

        for (const category of data.categories) {
            const res = await fetch(`${API_BASE}/getProductsByCategory?category=${encodeURIComponent(category)}`, { credentials: 'include' });
            const payload = await res.json();
            if (!payload.success || !Array.isArray(payload.products)) continue;

            const match = payload.products.find(p => String(p.crop_name || '').trim().toLowerCase() === target);
            if (match) {
                const catSel = document.getElementById('productCategory');
                if (catSel) catSel.value = category;
                loadProductsByCategory(category, 'productMaster', match.id);

                const nameField = document.getElementById('productName');
                if (nameField) nameField.value = match.crop_name;
                return;
            }
        }
    } catch (err) {
        console.warn('Auto-select category failed:', err);
    }
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) return;

    fetch(`${API_BASE}/delete/${id}`, {
        method: 'POST',
        credentials: 'include'
    })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showNotification('Product deleted successfully', 'success');
                loadFarmerProducts();
            } else {
                showNotification(res.error || 'Failed to delete product', 'error');
            }
        })
        .catch(err => {
            console.error('Delete error:', err);
            showNotification('Failed to delete product. Please try again.', 'error');
        });
}

// Export functions
window.editProduct = editProduct;
window.deleteProduct = deleteProduct;
window.loadFarmerProducts = loadFarmerProducts;
window.populateProductsTable = populateProductsTable;

let productsPageInitialized = false;

function initializeProductsPage() {
    if (productsPageInitialized) return;

    initializeProductForms();
    initializeEditForm();
    loadFarmerProducts();
    loadDistricts();
    loadCategories();

    const productDistrict = document.getElementById('productDistrict');
    if (productDistrict && !productDistrict.dataset.bound) {
        productDistrict.addEventListener('change', function () {
            loadTowns(this.value, 'productTown');
        });
        productDistrict.dataset.bound = '1';
    }

    const editDistrict = document.getElementById('editProductDistrict');
    if (editDistrict && !editDistrict.dataset.bound) {
        editDistrict.addEventListener('change', function () {
            loadTowns(this.value, 'editProductTown');
        });
        editDistrict.dataset.bound = '1';
    }

    const productCategory = document.getElementById('productCategory');
    if (productCategory && !productCategory.dataset.bound) {
        productCategory.addEventListener('change', function () {
            loadProductsByCategory(this.value, 'productMaster');
        });
        productCategory.dataset.bound = '1';
    }

    const editCategory = document.getElementById('editProductCategory');
    if (editCategory && !editCategory.dataset.bound) {
        editCategory.addEventListener('change', function () {
            loadProductsByCategory(this.value, 'editProductMaster');
        });
        editCategory.dataset.bound = '1';
    }

    const productMaster = document.getElementById('productMaster');
    if (productMaster && !productMaster.dataset.bound) {
        productMaster.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const nameField = document.getElementById('productName');
            if (!nameField) {
                return;
            }

            if (selectedOption && selectedOption.dataset.name) {
                nameField.value = selectedOption.dataset.name;
                nameField.readOnly = true;
            } else {
                nameField.readOnly = false;
            }
        });
        productMaster.dataset.bound = '1';
    }

    const editMaster = document.getElementById('editProductMaster');
    if (editMaster && !editMaster.dataset.bound) {
        editMaster.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const nameField = document.getElementById('editProductName');
            if (!nameField) {
                return;
            }

            if (selectedOption && selectedOption.dataset.name) {
                nameField.value = selectedOption.dataset.name;
                nameField.readOnly = true;
            } else {
                nameField.readOnly = false;
            }
        });
        editMaster.dataset.bound = '1';
    }

    prefillFromAcceptedCropRequest();

    productsPageInitialized = true;
}

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeProductsPage);
} else {
    initializeProductsPage();
}
