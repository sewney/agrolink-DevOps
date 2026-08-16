<?php $mode = (string)($gatewayViewMode ?? 'checkout'); ?>

<?php if ($mode === 'checkout'): ?>
    <div class="content-header">
        <h1 class="content-title">SecurePay Checkout</h1>
        <p class="content-subtitle">Enter card details to complete your payment securely.</p>
    </div>

    <div class="content-card securepay-card">
        <div class="securepay-head">
            <div class="securepay-brand">SecurePay</div>
            <p class="securepay-head-copy">Card details are validated and processed temporarily only. AgroLink does not save card numbers or CVV.</p>
        </div>

        <div class="securepay-layout">
            <div class="securepay-form-wrap">
                <h3 class="securepay-section-title">Card Details</h3>

                <form id="securePayForm" method="POST" action="<?= ROOT ?>/payment/process" novalidate>
                    <input type="hidden" name="order_id" value="<?= (int)$orderIds[0] ?>">
                    <input type="hidden" name="order_ids" value="<?= esc($orderIdsQuery) ?>">

                    <div class="form-group">
                        <label for="spCardHolder">Card Holder Name</label>
                        <input type="text" id="spCardHolder" name="card_holder_name" class="form-control" maxlength="80" autocomplete="cc-name" required>
                    </div>

                    <div class="form-group">
                        <label for="spCardNumber">Card Number</label>
                        <input type="text" id="spCardNumber" name="card_number" class="form-control" maxlength="23" inputmode="numeric" autocomplete="cc-number" placeholder="1234 5678 9012 3456" required>
                    </div>

                    <div class="securepay-inline-grid">
                        <div class="form-group">
                            <label for="spExpiryMonth">Expiry Month</label>
                            <input type="text" id="spExpiryMonth" name="expiry_month" class="form-control" maxlength="2" inputmode="numeric" placeholder="MM" required>
                        </div>
                        <div class="form-group">
                            <label for="spExpiryYear">Expiry Year</label>
                            <input type="text" id="spExpiryYear" name="expiry_year" class="form-control" maxlength="2" inputmode="numeric" placeholder="YY" required>
                        </div>
                        <div class="form-group">
                            <label for="spCvv">CVV</label>
                            <input type="password" id="spCvv" name="cvv" class="form-control" maxlength="4" inputmode="numeric" autocomplete="off" placeholder="CVV" required>
                        </div>
                    </div>

                    <div id="securePayError" class="securepay-error is-hidden" role="alert"></div>

                    <button type="submit" id="securePaySubmitBtn" class="btn btn-primary btn-large securepay-submit-btn">
                        Pay Now
                    </button>

                    <div id="securePayProcessing" class="securepay-processing is-hidden" aria-live="polite">
                        <span class="securepay-loader" aria-hidden="true"></span>
                        <span>Processing payment... Redirecting to bank...</span>
                    </div>
                </form>
            </div>

            <div class="securepay-summary-wrap">
                <h3 class="securepay-section-title">Order Summary</h3>
                <ul class="securepay-order-list">
                    <?php foreach ($orders as $order): ?>
                        <li>
                            <span>Order #<?= (int)$order->id ?></span>
                            <strong>Rs. <?= number_format((float)($order->order_total ?? 0), 2) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="securepay-total-row">
                    <span>Total Due</span>
                    <strong>Rs. <?= number_format((float)$totalAmount, 2) ?></strong>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($mode === 'success'): ?>
    <div class="content-header">
        <h1 class="content-title">Payment Successful</h1>
        <p class="content-subtitle">Your payment was completed through SecurePay.</p>
    </div>

    <div class="content-card securepay-result-card securepay-result-success">
        <div class="securepay-result-icon" aria-hidden="true">✓</div>
        <h3>Payment Confirmed</h3>
        <p>Your order is now processing and the farmer will prepare it for pickup.</p>

        <ul class="securepay-order-list">
            <?php foreach ($orders as $order): ?>
                <li>
                    <span>Order #<?= (int)$order->id ?></span>
                    <strong>Rs. <?= number_format((float)($order->order_total ?? 0), 2) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="securepay-total-row">
            <span>Total Paid</span>
            <strong>Rs. <?= number_format((float)$totalAmount, 2) ?></strong>
        </div>

        <div class="securepay-result-actions">
            <a class="btn btn-primary" href="<?= ROOT ?>/buyerorders">View Orders</a>
            <a class="btn btn-secondary" href="<?= ROOT ?>/buyerdashboard">Back to Dashboard</a>
        </div>
    </div>
<?php elseif ($mode === 'failed'): ?>
    <div class="content-header">
        <h1 class="content-title">Payment Failed</h1>
        <p class="content-subtitle">Your payment could not be completed in SecurePay.</p>
    </div>

    <div class="content-card securepay-result-card securepay-result-failed">
        <div class="securepay-result-icon" aria-hidden="true">!</div>
        <h3>Payment Could Not Be Completed</h3>
        <p>Card validation or gateway simulation failed. No card details were saved.</p>

        <ul class="securepay-order-list">
            <?php foreach ($orders as $order): ?>
                <li>
                    <span>Order #<?= (int)$order->id ?></span>
                    <strong>Rs. <?= number_format((float)($order->order_total ?? 0), 2) ?></strong>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="securepay-total-row">
            <span>Pending Amount</span>
            <strong>Rs. <?= number_format((float)$totalAmount, 2) ?></strong>
        </div>

        <div class="securepay-result-actions">
            <a class="btn btn-primary" href="<?= esc($retryUrl) ?>">Retry SecurePay</a>
            <a class="btn btn-secondary" href="<?= ROOT ?>/buyerorders">Go to Orders</a>
        </div>
    </div>
<?php endif; ?>