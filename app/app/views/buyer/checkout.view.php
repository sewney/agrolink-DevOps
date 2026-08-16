<div class="content-header">
    <h1 class="content-title">Checkout</h1>
    <p class="content-subtitle">Review your order and complete your purchase</p>
</div>

<div class="checkout-container">
    <div class="checkout-layout">
        <!-- Left Column: Delivery & Review Order -->
        <div class="checkout-main">
            <!-- Delivery Information Form (shown if buyer hasn't added details) -->
            <?php if (!$hasDeliveryDetails): ?>
                <div class="checkout-section" id="delivery-section">
                    <h2 class="checkout-section-title">Add a shipping address</h2>
                    <p class="checkout-section-subtitle">To continue, we need your contact information.</p>

                    <form id="deliveryForm" class="delivery-form">
                        <div class="form-group">
                            <label for="country" class="form-label">Country or region</label>
                            <select id="country" name="country" class="form-control" required>
                                <option value="Sri Lanka" selected>Sri Lanka</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="delivery_address" class="form-label">Street Address <span class="required">*</span></label>
                            <input type="text" id="delivery_address" name="delivery_address" class="form-control"
                                placeholder="Enter your street address" required>
                        </div>

                        <div class="form-group">
                            <label for="address2" class="form-label">Street Address 2 (Optional)</label>
                            <input type="text" id="address2" name="address2" class="form-control"
                                placeholder="Apartment, suite, etc. (optional)">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="state" class="form-label">District <span class="required">*</span></label>
                                <select id="state" name="state" class="form-control" required>
                                    <option value="" selected disabled>Select district</option>
                                    <?php foreach (($districtOptions ?? []) as $district): ?>
                                        <option value="<?= htmlspecialchars($district) ?>"><?= htmlspecialchars($district) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="zipCode" class="form-label">Zip Code</label>
                                <input type="text" id="zipCode" name="zipCode" class="form-control"
                                    placeholder="Postal code">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="city" class="form-label">Nearest City <span class="required">*</span></label>
                            <select id="city" name="city" class="form-control" required>
                                <option value="" selected disabled>Select nearest city</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="phone" class="form-label">Phone number <span class="required">*</span></label>
                            <div class="phone-input-group">
                                <select id="countryCode" class="form-control phone-code" disabled>
                                    <option value="+94" selected>🇱🇰 +94</option>
                                </select>
                                <input type="tel" id="phone" name="phone" class="form-control phone-input"
                                    placeholder="Enter your phone number" required>
                            </div>
                            <div class="form-checkbox">
                                <input type="checkbox" id="isLandline" name="isLandline">
                                <label for="isLandline">Phone number is a landline</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-large btn-continue checkout-continue-btn">
                            Continue
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Ship To Section (if delivery details exist) -->
                <div class="checkout-section" id="ship-to-section">
                    <h2 class="checkout-section-title">Ship to</h2>
                    <div class="ship-to-info">
                        <div class="ship-to-name"><?= htmlspecialchars(authUserName() ?: 'Buyer') ?></div>
                        <div class="ship-to-address">
                            <?= htmlspecialchars($buyerProfile->delivery_address ?? '') ?><br>
                            <?= htmlspecialchars($buyerProfile->city ?? '') ?>
                            <?php if (!empty($buyerProfile->city)): ?>
                                , Sri Lanka
                            <?php endif; ?>
                        </div>
                        <div class="ship-to-phone">
                            <?= htmlspecialchars($buyerProfile->phone ?? '') ?>
                        </div>
                        <a href="<?= ROOT ?>/buyerprofile" class="change-link">Change</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Review Order Section -->
            <div class="checkout-section<?= !$hasDeliveryDetails ? ' checkout-hidden' : '' ?>" id="review-order-section">
                <h2 class="checkout-section-title">Review order</h2>

                <?php
                // Group items by location group
                $itemsByGroup = [];
                foreach ($cartItems as $item) {
                    $groupKey = $item->group_key ?? 'unknown';
                    if (!isset($itemsByGroup[$groupKey])) {
                        $itemsByGroup[$groupKey] = [
                            'farmer_name' => $item->farmer_name ?? 'Unknown Farmer',
                            'pickup_location' => $item->pickup_location_name ?? 'Unknown Location',
                            'items' => []
                        ];
                    }
                    $itemsByGroup[$groupKey]['items'][] = $item;
                }
                ?>

                <?php foreach ($itemsByGroup as $groupKey => $groupData): ?>
                    <div class="order-seller-block">
                        <div class="seller-info" style="display:flex; flex-direction:column; gap:5px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span class="seller-name"><?= htmlspecialchars($groupData['farmer_name']) ?></span>
                          <!-- <span class="seller-feedback">99.6% positive feedback</span> -->
                            </div>
                            <div style="font-size:0.85rem; color:#666;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:text-bottom;margin-right:2px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg> 
                                Ships from: <?= htmlspecialchars($groupData['pickup_location']) ?>
                            </div>
                        </div>

                        <?php foreach ($groupData['items'] as $item): ?>
                            <div class="order-item">
                                <div class="order-item-image-small">
                                    <?php
                                    $imgFile = '';
                                    if (!empty($item->product_image) && strlen($item->product_image) > 2 && strpos($item->product_image, '.') !== false) {
                                        $imgFile = $item->product_image;
                                    } elseif (!empty($item->product_image_db)) {
                                        $imgFile = $item->product_image_db;
                                    }
                                    ?>
                                    <?php if (!empty($imgFile) && file_exists("assets/images/products/" . $imgFile)): ?>
                                        <img src="<?= ROOT ?>/assets/images/products/<?= htmlspecialchars($imgFile) ?>"
                                            alt="<?= htmlspecialchars($item->product_name) ?>" class="order-item-image">
                                    <?php else: ?>
                                        <img src="<?= ROOT ?>/assets/images/default-product.svg"
                                            alt="<?= htmlspecialchars($item->product_name) ?>" class="order-item-image order-item-image-fallback">
                                    <?php endif; ?>
                                </div>
                                <div class="order-item-details">
                                    <h4 class="order-item-name"><?= htmlspecialchars($item->product_name) ?></h4>
                                    <div class="order-item-quantity">
                                        <label>Quantity:</label>
                                        <?php
                                        $availableQty = $item->available_quantity ?? 0;
                                        $maxQuantity = $availableQty;
                                        $currentQuantity = min($item->quantity, $maxQuantity);

                                        if ($maxQuantity <= 0):
                                        ?>
                                            <span class="checkout-out-of-stock">Out of Stock</span>
                                        <?php else: ?>
                                            <input type="number" class="quantity-select" style="width: 80px; padding: 5px; border: 1px solid #ddd; border-radius: 4px;" 
                                                data-product-id="<?= $item->product_id ?>"
                                                data-max-quantity="<?= $availableQty ?>"
                                                value="<?= $currentQuantity ?>"
                                                min="1" max="<?= $availableQty ?>"
                                                onchange="updateCheckoutQuantity(<?= $item->product_id ?>, this.value, <?= $availableQty ?>)">
                                            <span class="checkout-available-qty">
                                                (<?= $availableQty ?> kg available)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-item-delivery">
                                        <div class="delivery-info">
                                            <span class="delivery-label">Estimated delivery:</span>
                                            <span class="delivery-date"><?= date('M d', strtotime('+3 days')) ?> - <?= date('M d', strtotime('+7 days')) ?></span>
                                        </div>
                                        <div class="shipping-method">Standard Shipping</div>
                                    </div>
                                    <div class="order-item-price">
                                        Rs. <?= number_format($item->product_price * $item->quantity, 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Column: Order Summary (Sticky) -->
        <div class="checkout-sidebar">
            <div class="order-summary-card">
                <h3 class="order-summary-title">Order Summary</h3>

                <div class="order-summary-row">
                    <span>Item (<?= $cartItemCount ?>):</span>
                    <span class="order-summary-value">Rs. <?= number_format($cartTotal, 2) ?></span>
                </div>

                <div class="order-summary-row">
                    <span>Shipping:</span>
                    <span class="order-summary-value">Rs. <?= number_format($deliveryFee, 2) ?></span>
                </div>

                <?php if ($shippingCalculation && $shippingCalculation['success']): ?>
                    <div class="order-summary-shipping-details checkout-shipping-details">
                        <?php
                        // Handle both single calculation and multiple calculations (multiple farmers)
                        $calc = $shippingCalculation['calculation'];
                        if (isset($calc['calculations']) && is_array($calc['calculations']) && !empty($calc['calculations'])) {
                            // Multiple farmers - show summary
                            $firstCalc = $calc['calculations'][0];
                        ?>
                            <div class="checkout-mt-8">
                                <span>Distance: <?= $firstCalc['total_distance_km'] ?? 'N/A' ?> km</span>
                            </div>
                            <div class="checkout-mt-4">
                                <span>Vehicle: <?= htmlspecialchars($firstCalc['selected_vehicle']['name'] ?? 'N/A') ?></span>
                            </div>
                            <?php if ($calc['multiple_farmers'] ?? false): ?>
                                <div class="checkout-mt-4 checkout-shipping-farmer-note">
                                    (<?= $calc['farmer_count'] ?> farmers)
                                </div>
                            <?php endif; ?>
                        <?php } else {
                            // Single calculation structure
                        ?>
                            <div class="checkout-mt-8">
                                <span>Distance: <?= $calc['total_distance_km'] ?? 'N/A' ?> km</span>
                            </div>
                            <div class="checkout-mt-4">
                                <span>Vehicle: <?= htmlspecialchars($calc['selected_vehicle']['name'] ?? 'N/A') ?></span>
                            </div>
                        <?php } ?>
                    </div>
                <?php endif; ?>

                <div class="order-summary-total">
                    <span class="order-summary-total-label">Order total:</span>
                    <span class="order-summary-total-amount">Rs. <?= number_format($orderTotal, 2) ?></span>
                </div>

                <div class="order-summary-note">
                    <p>With this purchase you agree to the AgroLink <a href="#">Terms and Conditions</a>.</p>
                </div>

                <button id="confirmPayBtn" class="btn btn-primary btn-large btn-confirm-pay<?= !$hasDeliveryDetails ? ' checkout-hidden' : '' ?>"
                    onclick="confirmPayment()">
                    Confirm and pay
                </button>

                <!-- SecurePay section (shown after clicking Confirm and pay) -->
                <div id="paymentMethodSection" class="checkout-payment-method-section checkout-hidden">
                    <div class="checkout-gateway-brand">SecurePay</div>
                    <p class="checkout-gateway-note">Secure checkout powered by SecurePay.</p>
                    <button id="finalConfirmBtn" class="btn btn-primary btn-large btn-complete-order" onclick="finalConfirmOrder()">
                        Proceed to SecurePay
                    </button>
                    <div id="checkoutGatewaySpinner" class="checkout-gateway-spinner checkout-hidden" aria-live="polite">
                        <span class="checkout-gateway-spinner-dot" aria-hidden="true"></span>
                        <span>Redirecting to bank...</span>
                    </div>
                </div>

                <p class="payment-message<?= !$hasDeliveryDetails ? '' : ' checkout-hidden' ?>">
                    Please complete delivery information to proceed
                </p>

                <div class="order-summary-guarantee">
                    <span class="guarantee-icon"></span>
                    <span>Purchase protected by <a href="#">AgroLink Money Back Guarantee</a></span>
                </div>
            </div>
        </div>
    </div>
</div>