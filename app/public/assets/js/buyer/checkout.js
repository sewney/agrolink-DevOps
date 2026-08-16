document.addEventListener('DOMContentLoaded', function() {
    const deliveryForm = document.getElementById('deliveryForm');
    const stateSelect = document.getElementById('state');
    const citySelect = document.getElementById('city');

    if (stateSelect && citySelect) {
        stateSelect.addEventListener('change', function() {
            const district = this.value;
            citySelect.innerHTML = '<option value="" selected disabled>Loading...</option>';
            citySelect.disabled = true;

            if (!district) {
                citySelect.innerHTML = '<option value="" selected disabled>Select nearest city</option>';
                return;
            }

            fetch(`${window.APP_ROOT}/Checkout/getTownsByDistrictName?district=${encodeURIComponent(district)}`)
                .then(response => response.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="" selected disabled>Select nearest city</option>';
                    citySelect.disabled = false;
                    
                    if (data.success && data.towns) {
                        data.towns.forEach(town => {
                            const option = document.createElement('option');
                            option.value = town.town_name;
                            option.textContent = town.town_name;
                            citySelect.appendChild(option);
                        });
                    } else {
                        citySelect.innerHTML = '<option value="" selected disabled>No cities found</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching towns:', error);
                    citySelect.innerHTML = '<option value="" selected disabled>Error loading cities</option>';
                    citySelect.disabled = false;
                });
        });
    }

    if (deliveryForm) {
        deliveryForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const phone = document.getElementById('phone').value.trim();
            const city = document.getElementById('city').value.trim();
            const deliveryAddress = document.getElementById('delivery_address').value.trim();
            const district = document.getElementById('state').value.trim();

            if (!phone || !city || !deliveryAddress || !district) {
                showNotification('Please fill in all required fields', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('phone', phone);
            formData.append('city', city);
            formData.append('delivery_address', deliveryAddress);
            formData.append('address2', document.getElementById('address2')?.value || '');
            formData.append('zipCode', document.getElementById('zipCode')?.value || '');
            formData.append('state', district);

            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Saving...';

            fetch(window.APP_ROOT + '/Checkout/saveDeliveryDetails', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('delivery-section')?.classList.add('checkout-hidden');
                        document.getElementById('review-order-section')?.classList.remove('checkout-hidden');
                        document.getElementById('confirmPayBtn')?.classList.remove('checkout-hidden');
                        document.querySelector('.payment-message')?.classList.add('checkout-hidden');

                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        showNotification(data.message || 'Failed to save delivery details', 'error');
                        btn.disabled = false;
                        btn.textContent = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred while saving delivery details: ' + error.message, 'error');
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    }
});

function updateCheckoutQuantity(productId, quantity, maxQuantity) {
    if (maxQuantity && quantity > maxQuantity) {
        showNotification('Cannot select more than ' + maxQuantity + ' kg. Only ' + maxQuantity + ' kg available.', 'warning');
        const select = document.querySelector(`select[data-product-id="${productId}"]`);
        if (select) {
            select.value = maxQuantity;
            quantity = maxQuantity;
        }
        return;
    }

    if (quantity <= 0) {
        showNotification('Quantity must be at least 1', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

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
                showNotification('Failed to update quantity: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating quantity', 'error');
        });
}

function confirmPayment() {
    const confirmBtn = document.getElementById('confirmPayBtn');
    const paymentSection = document.getElementById('paymentMethodSection');

    if (confirmBtn && paymentSection) {
        confirmBtn.classList.add('checkout-hidden');
        paymentSection.classList.remove('checkout-hidden');
        paymentSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function finalConfirmOrder() {
    if (!confirm('Are you sure you want to place this order?')) {
        return;
    }

    const btn = document.getElementById('finalConfirmBtn');
    const spinner = document.getElementById('checkoutGatewaySpinner');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Processing payment...';
    if (spinner) spinner.classList.remove('checkout-hidden');

    const formData = new FormData();

    fetch(window.APP_ROOT + '/Checkout/placeOrder', {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const redirectUrl = data.redirect || null;
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                    return;
                }
                window.location.href = window.APP_ROOT + '/buyerorders';
            } else {
                showNotification(data.message || 'Failed to place order', 'error');
                btn.disabled = false;
                btn.textContent = originalText;
                if (spinner) spinner.classList.add('checkout-hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while placing order: ' + error.message, 'error');
            btn.disabled = false;
            btn.textContent = originalText;
            if (spinner) spinner.classList.add('checkout-hidden');
        });
}

window.updateCheckoutQuantity = updateCheckoutQuantity;
window.confirmPayment = confirmPayment;
window.finalConfirmOrder = finalConfirmOrder;
