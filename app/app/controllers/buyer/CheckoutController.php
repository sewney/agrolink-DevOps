<?php

class CheckoutController
{
    use Controller;

    protected $cartModel;
    protected $buyerModel;
    protected $orderModel;
    protected $productModel;
    protected $farmerModel;
    protected $shippingCalculator;

    public function __construct()
    {
        $this->cartModel = new CartModel();
        $this->buyerModel = new BuyerModel();
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductsModel();
        $this->farmerModel = new FarmerModel();

        // Initialize shipping calculator
        try {
            $pdo = new PDO("mysql:host=" . DBHOST . ";dbname=" . DBNAME, DBUSER, DBPASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->shippingCalculator = new SimpleShippingCalculator($pdo);
        } catch (PDOException $e) {
            $this->debugLog('Shipping calculator initialization error: ' . $e->getMessage());
            $this->shippingCalculator = null;
        }
    }

    /**
     * Display checkout page
     */
    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $user_id = authUserId();

        // Check if this is a "Buy Now" checkout (single product) or a normal cart checkout
        $isCartGET = isset($_GET['cart']) && $_GET['cart'] == '1';
        $isBuyNowGET = isset($_GET['buy_now']) && $_GET['buy_now'] == '1';

        if ($isCartGET) {
            if (isset($_SESSION['buy_now_product_id'])) {
                unset($_SESSION['buy_now_product_id']);
            }
            $isBuyNow = false;
        } elseif ($isBuyNowGET && isset($_GET['product_id'])) {
            $_SESSION['buy_now_product_id'] = (int)$_GET['product_id'];
            $isBuyNow = true;
        } else {
            $isBuyNow = isset($_SESSION['buy_now_product_id']);
        }

        // Get cart items with product details including available quantity
        $cartItems = $this->cartModel->getCartByUserId($user_id);
        $cartItemCount = $this->cartModel->getCartItemCount($user_id);
        $cartTotal = $this->cartModel->getCartTotal($user_id);

        // Enrich cart items with product quantity information
        foreach ($cartItems as $item) {
            $product = $this->productModel->getById($item->product_id);
            if ($product) {
                $item->available_quantity = $product->quantity ?? 0;
                // Ensure cart quantity doesn't exceed available quantity
                if ($item->quantity > $item->available_quantity) {
                    // Update cart quantity to max available
                    $this->cartModel->updateQuantity($user_id, $item->product_id, $item->available_quantity);
                    $item->quantity = $item->available_quantity;
                }

                $locationData = $this->getFarmerProductLocation($product->farmer_id, $product);
                $item->group_key = 'farmer_' . $product->farmer_id . '_dist_' . ($locationData['district_id'] ?: '0') . '_town_' . ($locationData['town_id'] ?: '0');
                
                // Set readable pickup location
                if (!empty($product->location) && $product->location !== 'auto') {
                    $item->pickup_location_name = $product->location;
                } else {
                    $item->pickup_location_name = $locationData['district_name'] ? ($locationData['district_name']) : 'Unknown Location';
                }
            } else {
                $item->available_quantity = 0;
                $item->group_key = 'farmer_0_dist_0_town_0';
                $item->pickup_location_name = 'Unknown Location';
            }
        }

        // If Buy Now mode, filter to show only that product
        if ($isBuyNow && isset($_SESSION['buy_now_product_id'])) {
            $buyNowProductId = $_SESSION['buy_now_product_id'];
            $cartItems = array_filter($cartItems, function ($item) use ($buyNowProductId) {
                return $item->product_id == $buyNowProductId;
            });
            $cartItems = array_values($cartItems); // Re-index array

            // Recalculate totals for Buy Now item only
            $cartTotal = 0;
            $cartItemCount = 0;
            foreach ($cartItems as $item) {
                $cartTotal += $item->product_price * $item->quantity;
                $cartItemCount += $item->quantity;
            }
        }

        // Check if cart is empty
        if (empty($cartItems) || $cartItemCount === 0) {
            $_SESSION['error'] = 'Your cart is empty. Please add items before checkout.';
            redirect('Cart');
            return;
        }

        // Get buyer profile to check if delivery details exist
        $buyerProfile = $this->buyerModel->getProfileByUserId($user_id);
        $hasDeliveryDetails = $this->buyerModel->hasDeliveryDetails($user_id);

        // Calculate shipping cost using shipping calculator.
        // No hardcoded fallback fee is allowed.
        $deliveryFee = 0.00;
        $shippingCalculation = null;
        $shippingError = null;

        if ($hasDeliveryDetails && !empty($cartItems)) {
            if (!$this->shippingCalculator) {
                $shippingError = 'Shipping service is currently unavailable. Please try again.';
            } else {
                $shippingCalculation = $this->calculateShippingForCart($cartItems, $buyerProfile);
                if ($shippingCalculation && !empty($shippingCalculation['success'])) {
                    $deliveryFee = (float)$shippingCalculation['calculation']['total_shipping_cost_lkr'];
                } else {
                    $shippingError = (string)($shippingCalculation['error'] ?? 'Shipping calculation failed. Please verify your delivery location.');
                }
            }
        }

        $orderTotal = $cartTotal + $deliveryFee;

        $data = [
            'cartItems' => $cartItems ?: [],
            'cartItemCount' => $cartItemCount,
            'cartTotal' => $cartTotal,
            'deliveryFee' => $deliveryFee,
            'orderTotal' => $orderTotal,
            'buyerProfile' => $buyerProfile,
            'hasDeliveryDetails' => $hasDeliveryDetails,
            'districtOptions' => $this->getDistrictNames(),
            'isBuyNow' => $isBuyNow,
            'shippingCalculation' => $shippingCalculation,
            'shippingError' => $shippingError,
            'pageTitle' => 'Checkout',
            'activePage' => 'checkout',
            'contentView' => 'buyer/checkout.view.php',
            'pageStyles' => 'checkout.css',
            'pageScript' => 'checkout.js'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Calculate shipping cost for cart items
     * Handles multiple farmers by grouping items by farmer and calculating shipping for each
     */
    private function calculateShippingForCart($cartItems, $buyerProfile)
    {
        if (!$this->shippingCalculator || empty($cartItems)) {
            return [
                'success' => false,
                'error' => 'Shipping calculator is unavailable',
            ];
        }

        // Get buyer's district and town IDs
        $buyerDistrictId = $this->findDistrictIdByName($buyerProfile->district ?? '');
        if (!$buyerDistrictId) {
            return [
                'success' => false,
                'error' => 'Invalid or missing buyer district',
            ];
        }
        $buyerTownId = $this->getTownIdByName($buyerProfile->city ?? '', $buyerDistrictId);
        if (!$buyerTownId) {
            return [
                'success' => false,
                'error' => 'Invalid or missing buyer town',
            ];
        }

        // Group cart items by farmer AND location
        $itemsByGroup = [];
        foreach ($cartItems as $item) {
            $product = $this->productModel->getById($item->product_id);
            if (!$product) {
                return [
                    'success' => false,
                    'error' => 'Product not found in cart',
                ];
            }

            $farmerId = (int)($product->farmer_id ?? 0);
            if ($farmerId <= 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid farmer mapping for one or more cart items',
                ];
            }

            $itemWeight = $this->calculateItemWeightKg($item, $product);
            if ($itemWeight <= 0) {
                return [
                    'success' => false,
                    'error' => 'Missing crop volume factor for product: ' . ($item->product_name ?? 'Unknown item'),
                ];
            }

            $locationData = $this->getFarmerProductLocation($farmerId, $product);
            $districtId = $locationData['district_id'];
            $townId = $locationData['town_id'];

            if (!$districtId) {
                return [
                    'success' => false,
                    'error' => 'Invalid farmer district for shipping',
                ];
            }

            if (!$townId) {
                return [
                    'success' => false,
                    'error' => 'Invalid farmer town for shipping',
                ];
            }

            $groupKey = "farmer_{$farmerId}_dist_{$districtId}_town_{$townId}";

            if (!isset($itemsByGroup[$groupKey])) {
                $itemsByGroup[$groupKey] = [
                    'farmer_id' => $farmerId,
                    'district_id' => $districtId,
                    'town_id' => $townId,
                    'items' => [],
                    'total_weight_kg' => 0.0,
                    'first_product' => $product,
                ];
            }

            $itemsByGroup[$groupKey]['items'][] = [
                'cart_item' => $item,
                'product' => $product,
                'item_weight_kg' => $itemWeight,
            ];
            $itemsByGroup[$groupKey]['total_weight_kg'] += $itemWeight;
        }

        if (empty($itemsByGroup)) {
            return [
                'success' => false,
                'error' => 'No valid items available for shipping calculation',
            ];
        }

        // Calculate shipping for each location group and sum them up
        $totalShippingCost = 0.0;
        $allCalculations = [];
        $selectedVehicles = [];

        foreach ($itemsByGroup as $groupKey => $groupData) {
            $totalWeight = (float)$groupData['total_weight_kg'];
            $farmerDistrictId = $groupData['district_id'];
            $farmerTownId = $groupData['town_id'];

            if ($totalWeight <= 0) {
                return [
                    'success' => false,
                    'error' => 'Invalid shipping weight for one or more farmer groups',
                ];
            }

            $params = [
                'pickup_district_id' => $farmerDistrictId,
                'pickup_town_id' => $farmerTownId,
                'delivery_district_id' => $buyerDistrictId,
                'delivery_town_id' => $buyerTownId,
                'crop_name' => 'mixed',
                'weight_kg' => round($totalWeight, 2),
                'weight_already_adjusted' => true,
            ];

            $calculation = $this->shippingCalculator->calculateShippingCost($params);

            if ($calculation && $calculation['success']) {
                $totalShippingCost += $calculation['calculation']['total_shipping_cost_lkr'];
                $allCalculations[] = $calculation['calculation'];
                $selectedVehicles[] = $calculation['calculation']['selected_vehicle'];
            } else {
                return [
                    'success' => false,
                    'error' => (string)($calculation['error'] ?? 'Shipping calculation failed for one or more farmer groups'),
                ];
            }
        }

        if ($totalShippingCost <= 0) {
            return [
                'success' => false,
                'error' => 'Unable to determine shipping cost',
            ];
        }

        $groupCount = count($itemsByGroup);

        // Return combined result
        return [
            'success' => true,
            'calculation' => [
                'total_shipping_cost_lkr' => round($totalShippingCost, 2),
                'multiple_farmers' => $groupCount > 1,
                'farmer_count' => $groupCount,
                'calculations' => $allCalculations,
                'selected_vehicles' => $selectedVehicles
            ]
        ];
    }

    /**
     * Resolve district ID by name without fallback.
     */
    private function findDistrictIdByName($districtName)
    {
        $normalizedName = trim((string)$districtName);
        if ($normalizedName === '') {
            return null;
        }

        $dbModel = new CartModel();
        $sql = "SELECT id FROM districts WHERE district_name = :name LIMIT 1";
        $result = $dbModel->query($sql, ['name' => $normalizedName]);

        if ($result && is_array($result) && !empty($result)) {
            return (int)$result[0]->id;
        }

        return null;
    }

    /**
     * Get district ID by name
     */
    private function getDistrictIdByName($districtName)
    {
        return $this->findDistrictIdByName($districtName);
    }

    /**
     * Get district names for checkout dropdown.
     */
    private function getDistrictNames()
    {
        $dbModel = new CartModel();
        $sql = "SELECT district_name FROM districts ORDER BY district_name ASC";
        $result = $dbModel->query($sql);

        if ($result && is_array($result) && !empty($result)) {
            return array_map(static function ($row) {
                return (string)$row->district_name;
            }, $result);
        }

        return [
            'Ampara',
            'Anuradhapura',
            'Badulla',
            'Batticaloa',
            'Colombo',
            'Galle',
            'Gampaha',
            'Jaffna',
            'Kalutara',
            'Kandy',
            'Kegalle',
            'Kilinochchi',
            'Kurunegala',
            'Mannar',
            'Matale',
            'Matara',
            'Mullaitivu',
            'Nuwara Eliya',
            'Polonnaruwa',
            'Puttalam',
            'Ratnapura',
            'Trincomalee',
            'Vavuniya',
        ];
    }

    /**
     * Get town ID by name and district
     */
    private function getTownIdByName($townName, $districtId)
    {
        $districtId = (int)$districtId;
        if ($districtId <= 0) {
            return null;
        }

        $normalizedTownName = trim((string)$townName);
        if ($normalizedTownName === '') {
            return null;
        }

        // Product locations can be stored as "Town, District"; keep town-only for lookup.
        if (strpos($normalizedTownName, ',') !== false) {
            $parts = explode(',', $normalizedTownName, 2);
            $normalizedTownName = trim((string)$parts[0]);
        }

        if ($normalizedTownName === '') {
            return null;
        }

        $dbModel = new CartModel(); // Use existing model to access Database trait
        $sql = "SELECT id FROM towns WHERE town_name LIKE :name AND district_id = :district_id LIMIT 1";
        $result = $dbModel->query($sql, ['name' => '%' . $normalizedTownName . '%', 'district_id' => $districtId]);
        if ($result && is_array($result) && !empty($result)) {
            return $result[0]->id;
        }
        return null;
    }

    /**
     * Save delivery details (AJAX)
     */
    public function saveDeliveryDetails()
    {
        header('Content-Type: application/json');

        if (!hasRole('buyer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $user_id = authUserId();

        $data = [
            'phone' => trim($_POST['phone'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'street_name' => trim($_POST['delivery_address'] ?? ''),
            'apartment_code' => trim($_POST['address2'] ?? ''),
            'postal_code' => trim($_POST['zipCode'] ?? ''),
            'district' => trim($_POST['state'] ?? '')
        ];

        // Validate required fields
        if (empty($data['phone']) || empty($data['city']) || empty($data['street_name']) || empty($data['district'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Phone, city, district, and street address are required']);
            exit;
        }

        if (!$this->findDistrictIdByName($data['district'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Please select a valid district']);
            exit;
        }

        try {
            // Check if profile exists
            $existingProfile = $this->buyerModel->getProfileByUserId($user_id);

            if ($existingProfile && is_object($existingProfile)) {
                // Update existing profile
                $result = $this->buyerModel->updateProfile($user_id, $data);
            } else {
                // Create new profile
                $result = $this->buyerModel->createProfile($user_id, $data);
            }

            if ($result !== false) {
                // Verify the save by fetching the profile
                $savedProfile = $this->buyerModel->getProfileByUserId($user_id);
                if ($savedProfile && is_object($savedProfile)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Delivery details saved successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Details saved but could not verify. Please refresh the page.']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save delivery details. Please check your database connection and table structure.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Place order (AJAX)
     */
    public function placeOrder()
    {
        header('Content-Type: application/json');

        if (!hasRole('buyer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $user_id = authUserId();

        // Get cart items
        $cartItems = $this->cartModel->getCartByUserId($user_id);

        if (isset($_SESSION['buy_now_product_id'])) {
            $buyNowProductId = (int)$_SESSION['buy_now_product_id'];
            $cartItems = array_values(array_filter($cartItems, function ($item) use ($buyNowProductId) {
                return (int)$item->product_id === $buyNowProductId;
            }));
        }

        if (empty($cartItems)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => isset($_SESSION['buy_now_product_id'])
                    ? 'Selected Buy Now item is not in cart. Please try Buy Now again.'
                    : 'Cart is empty'
            ]);
            exit;
        }

        // Get buyer profile
        $buyerProfile = $this->buyerModel->getProfileByUserId($user_id);
        if (!$buyerProfile || !$this->buyerModel->hasDeliveryDetails($user_id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Please complete your delivery details first']);
            exit;
        }

        $deliveryDistrictId = $this->findDistrictIdByName($buyerProfile->district ?? '');
        $deliveryTownId = $this->getTownIdByName($buyerProfile->city ?? '', $deliveryDistrictId);
        if (!$deliveryDistrictId || !$deliveryTownId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid delivery location. Please update your district and city.']);
            exit;
        }

        // --- STEP 1: VALIDATE STOCK FOR ALL ITEMS ---
        $errors = [];
        $itemsByFarmer = []; // Group items by farmer for later

        foreach ($cartItems as $item) {
            $product = $this->productModel->getById($item->product_id);
            if (!$product) {
                $errors[] = "Product not found: {$item->product_name}";
                continue;
            }

            $availableQuantity = $product->quantity ?? 0;

            // Check for valid quantity
            if ($item->quantity <= 0) {
                $errors[] = "Item '{$item->product_name}' is out of stock. Please remove it from cart.";
                continue;
            }

            if ($item->quantity > $availableQuantity) {
                $errors[] = "Insufficient stock for {$item->product_name}. Only {$availableQuantity} kg available.";
            }

            // Group by farmer and location
            $farmerId = (int)($product->farmer_id ?? 0);

            if (!$farmerId) {
                $errors[] = "Could not find farmer for product: {$item->product_name}";
                continue;
            }

            $locationData = $this->getFarmerProductLocation($farmerId, $product);
            $groupKey = 'farmer_' . $farmerId . '_dist_' . ($locationData['district_id'] ?: '0') . '_town_' . ($locationData['town_id'] ?: '0');

            if (!isset($itemsByGroup[$groupKey])) {
                $itemsByGroup[$groupKey] = [];
            }
            $itemsByGroup[$groupKey][] = [
                'item' => $item,
                'product' => $product,
            ];
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Stock validation failed: ' . implode(', ', $errors)
            ]);
            exit;
        }

        if (empty($itemsByGroup)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'No valid items found for order placement.',
            ]);
            exit;
        }

        $orderIds = [];
        $overallTotal = 0.0;

        if (!$this->orderModel->beginTransaction()) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to start order transaction. Please try again.']);
            exit;
        }

        try {
            // --- STEP 2: LOOP AND CREATE SPLIT ORDERS ---
            foreach ($itemsByGroup as $groupKey => $groupEntries) {
                $farmerItems = [];
                $firstProduct = $groupEntries[0]['product'];
                $farmerId = (int)$firstProduct->farmer_id;
                
                foreach ($groupEntries as $entry) {
                    $farmerItems[] = $entry['item'];
                }

                // Calculate shipping for this specific group of items.
                $shippingCalculation = $this->calculateShippingForCart($farmerItems, $buyerProfile);
                if (!$shippingCalculation || empty($shippingCalculation['success'])) {
                    $shippingError = (string)($shippingCalculation['error'] ?? 'Shipping calculation failed');
                    throw new InvalidArgumentException('Shipping calculation failed for farmer #' . $farmerId . ': ' . $shippingError);
                }

                $shippingCost = (float)$shippingCalculation['calculation']['total_shipping_cost_lkr'];
                if ($shippingCost <= 0) {
                    throw new InvalidArgumentException('Shipping calculation returned an invalid fee for farmer #' . $farmerId);
                }

                // Calculate subtotal for this group.
                $cartTotal = 0.0;
                foreach ($groupEntries as $entry) {
                    $item = $entry['item'];
                    $cartTotal += (float)$item->product_price * (float)$item->quantity;
                }

                $orderTotal = $cartTotal + $shippingCost;
                $overallTotal += $orderTotal;

                $orderData = [
                    'buyer_id' => $user_id,
                    'total_amount' => $cartTotal,
                    'shipping_cost' => $shippingCost,
                    'order_total' => $orderTotal,
                    'payment_status' => 'pending',
                    'delivery_address' => ($buyerProfile->apartment_code ?? '') . ', ' . ($buyerProfile->street_name ?? ''),
                    'delivery_city' => $buyerProfile->city ?? '',
                    'delivery_district_id' => $deliveryDistrictId,
                    'delivery_town_id' => $deliveryTownId,
                    'delivery_phone' => $buyerProfile->phone ?? '',
                    'status' => 'pending_payment'
                ];

                $orderId = $this->orderModel->createOrder($orderData);

                if ($orderId === false || (int)$orderId <= 0) {
                    throw new RuntimeException('Failed to create sub-order for farmer #' . $farmerId);
                }

                $orderId = (int)$orderId;
                $orderIds[] = $orderId;

                // --- STEP 3: ADD ITEMS & UPDATE STOCK FOR THIS SUB-ORDER ---
                $totalWeight = 0.0;
                foreach ($groupEntries as $entry) {
                    $item = $entry['item'];
                    $product = $entry['product'];

                    $itemWeight = $this->calculateItemWeightKg($item, $product);
                    if ($itemWeight <= 0) {
                        throw new InvalidArgumentException('Missing crop volume factor for product: ' . $item->product_name);
                    }

                    $totalWeight += $itemWeight;

                    $itemData = [
                        'order_id' => $orderId,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'product_price' => $item->product_price,
                        'quantity' => $item->quantity,
                        'item_weight_kg' => $itemWeight,
                        'farmer_id' => $farmerId,
                        'product_full_address' => trim((string)($product->full_address ?? '')),
                    ];

                    $addResult = $this->orderModel->addOrderItem($itemData);

                    if (!$addResult) {
                        throw new RuntimeException('Failed to add item ' . $item->product_name . ' to order #' . $orderId);
                    }

                    if ($this->orderModel->updateProductQuantity($item->product_id, $item->quantity) === false) {
                        throw new RuntimeException('Failed to update stock for ' . $item->product_name);
                    }
                }

                $totalWeight = round($totalWeight, 2);

                if ($this->orderModel->updateOrderWeight($orderId, $totalWeight) === false) {
                    throw new RuntimeException('Failed to update total order weight for order #' . $orderId);
                }

                // --- STEP 4: CREATE DELIVERY REQUEST FOR THIS ORDER ---
                // We've already resolved deliveryDistrictId and farmerDistrictId successfully above
                $locationData = $this->getFarmerProductLocation($farmerId, $firstProduct);
                $farmerDistrictIdForDelivery = $locationData['district_id'] ?? $deliveryDistrictId; // Fallback should not happen but just in case
                $exactDistanceKm = $shippingCalculation['calculation']['calculations'][0]['total_distance_km'] ?? null;

                if (!$this->createDeliveryRequest($orderId, $user_id, $farmerId, $totalWeight, $shippingCost, $buyerProfile, $deliveryDistrictId, $farmerDistrictIdForDelivery, $exactDistanceKm)) {
                    throw new RuntimeException('Failed to create delivery request for order #' . $orderId);
                }
            }

            if (empty($orderIds)) {
                throw new RuntimeException('No valid orders were created.');
            }

            if ($this->cartModel->clearCart($user_id) === false) {
                throw new RuntimeException('Failed to clear cart after order creation.');
            }

            $this->clearBuyNow();

            if (!$this->orderModel->commit()) {
                throw new RuntimeException('Failed to commit order transaction.');
            }
        } catch (Throwable $e) {
            if ($this->orderModel->inTransaction()) {
                $this->orderModel->rollBack();
            }

            $this->debugLog('Checkout placeOrder failed: ' . $e->getMessage());

            $statusCode = $e instanceof InvalidArgumentException ? 422 : 500;
            http_response_code($statusCode);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to place order. Error: ' . $e->getMessage(),
            ]);
            exit;
        }

        $primaryOrderId = (int)$orderIds[0];
        $orderIdsQuery = implode(',', array_map('intval', $orderIds));

        echo json_encode([
            'success' => true,
            'message' => 'Order created. Redirecting to SecurePay...',
            'order_ids' => $orderIds,
            'order_total' => $overallTotal,
            'redirect' => ROOT . '/payment/checkout?order_id=' . $primaryOrderId . '&order_ids=' . urlencode($orderIdsQuery)
        ]);
        exit;
    }

    /**
     * Clear buy now session flag (called after order completion)
     */
    public function clearBuyNow()
    {
        if (isset($_SESSION['buy_now_product_id'])) {
            unset($_SESSION['buy_now_product_id']);
        }
    }

    private function getFarmerIdByProductId($productId)
    {
        $product = $this->productModel->getById($productId);
        return $product ? $product->farmer_id : null;
    }

    /**
     * Calculate effective item weight using crop volume factor.
     */
    private function calculateItemWeightKg($item, $product = null)
    {
        $quantity = (float)($item->quantity ?? 0);
        if ($quantity <= 0) {
            return 0.0;
        }

        $cropName = '';
        if ($product && !empty($product->name)) {
            $cropName = (string)$product->name;
        } elseif (!empty($item->product_name)) {
            $cropName = (string)$item->product_name;
        }

        $volumeFactor = $this->getProductWeight($cropName);
        if ($volumeFactor === null || $volumeFactor <= 0) {
            return 0.0;
        }

        return round($quantity * $volumeFactor, 2);
    }

    /**
     * Get crop volume factor from crop_volume_factors table.
     * Returns null when no active mapping exists.
     */
    private function getProductWeight($cropName)
    {
        if (trim((string)$cropName) === '') {
            return null;
        }

        $dbModel = new CartModel(); // Use existing model to access Database trait
        $sql = "SELECT volume_factor FROM crop_volume_factors WHERE LOWER(crop_name) = LOWER(:crop_name) LIMIT 1";
        $result = $dbModel->query($sql, ['crop_name' => $cropName]);

        if ($result && is_array($result) && !empty($result)) {
            $factor = (float)$result[0]->volume_factor;
            return $factor > 0 ? $factor : null;
        }

        return null;
    }

    /**
     * Create delivery request for transporters
     */
    private function createDeliveryRequest($orderId, $buyerId, $farmerId, $totalWeight, $shippingFee, $buyerProfile, $buyerDistrictIdInput = null, $farmerDistrictIdInput = null, $exactDistanceKm = null)
    {
        try {
            // Get buyer details
            $buyerSql = "SELECT name FROM users WHERE id = :id LIMIT 1";
            $dbModel = new CartModel();
            $buyerResult = $dbModel->query($buyerSql, ['id' => $buyerId]);
            $buyerName = $buyerResult && is_array($buyerResult) && !empty($buyerResult) ? $buyerResult[0]->name : 'Unknown';

            // Get farmer details
            $farmerSql = "SELECT u.name, fp.phone, fp.full_address, fp.district 
                         FROM users u 
                         LEFT JOIN farmer_profiles fp ON u.id = fp.user_id 
                         WHERE u.id = :id LIMIT 1";
            $farmerResult = $dbModel->query($farmerSql, ['id' => $farmerId]);

            if (!$farmerResult || empty($farmerResult)) {
                throw new RuntimeException("Failed to get farmer details for farmer_id: {$farmerId}");
            }

            $farmer = $farmerResult[0];
            $productPickupAddress = $this->getProductPickupAddressesForOrder((int)$orderId, (int)$farmerId);

            // Determine required vehicle type based on weight
            $vehicleTypeSql = "SELECT id FROM vehicle_types 
                              WHERE min_weight_kg <= :weight 
                              AND max_weight_kg >= :weight 
                              AND is_active = 1 
                              ORDER BY min_weight_kg ASC LIMIT 1";
            $vehicleTypeResult = $dbModel->query($vehicleTypeSql, ['weight' => $totalWeight]);
            $requiredVehicleTypeId = $vehicleTypeResult && !empty($vehicleTypeResult) ? $vehicleTypeResult[0]->id : null;

            if (!$requiredVehicleTypeId) {
                throw new RuntimeException("No eligible vehicle type found for order {$orderId} and weight {$totalWeight}kg");
            }

            // Resolve Districts (Use passed IDs first, then fallback)
            $buyerDistrictId = $buyerDistrictIdInput ?: $this->getDistrictIdByName($buyerProfile->district ?? '');
            $farmerDistrictId = $farmerDistrictIdInput ?: $this->getDistrictIdByName($farmer->district ?? '');

            if (!$buyerDistrictId) {
                throw new RuntimeException("Failed to get buyer district_id for district name: " . ($buyerProfile->district ?? ''));
            }

            if (!$farmerDistrictId) {
                throw new RuntimeException("Failed to get farmer district_id for district name: " . ($farmer->district ?? ''));
            }

            // Use passed exact distance if available, else null
            $distance = $exactDistanceKm;

            // Calculate commission and transporter fee
            $commissionFee = $shippingFee * 0.05;
            $transporterFee = $shippingFee * 0.95;

            // Prepare buyer full address
            $buyerFullAddress = trim(($buyerProfile->apartment_code ?? '') . ', ' .
                ($buyerProfile->street_name ?? '') . ', ' .
                ($buyerProfile->city ?? ''));

            // Insert delivery request
            $insertSql = "INSERT INTO delivery_requests (
                order_id, buyer_id, buyer_name, buyer_phone, buyer_address, buyer_city, buyer_district_id,
                farmer_id, farmer_name, farmer_phone, farmer_address, farmer_city, farmer_district_id,
                total_weight_kg, shipping_fee, commission_fee, distance_km, required_vehicle_type_id, status, created_at, updated_at
            ) VALUES (
                :order_id, :buyer_id, :buyer_name, :buyer_phone, :buyer_address, :buyer_city, :buyer_district_id,
                :farmer_id, :farmer_name, :farmer_phone, :farmer_address, :farmer_city, :farmer_district_id,
                :total_weight_kg, :shipping_fee, :commission_fee, :distance_km, :required_vehicle_type_id, 'pending', NOW(), NOW()
            )";

            $params = [
                'order_id' => $orderId,
                'buyer_id' => $buyerId,
                'buyer_name' => $buyerName,
                'buyer_phone' => $buyerProfile->phone ?? '',
                'buyer_address' => $buyerFullAddress,
                'buyer_city' => $buyerProfile->city ?? '',
                'buyer_district_id' => $buyerDistrictId,
                'farmer_id' => $farmerId,
                'farmer_name' => $farmer->name ?? 'Unknown',
                'farmer_phone' => $farmer->phone ?? '',
                'farmer_address' => $productPickupAddress !== '' ? $productPickupAddress : ($farmer->full_address ?? ''),
                'farmer_city' => $farmer->district ?? '',
                'farmer_district_id' => $farmerDistrictId,
                'total_weight_kg' => $totalWeight,
                'shipping_fee' => $transporterFee,
                'commission_fee' => $commissionFee,
                'distance_km' => $distance,
                'required_vehicle_type_id' => $requiredVehicleTypeId
            ];

            // Execute via raw PDO to catch explicit SQL exceptions instead of false
            $dbConnection = (new CartModel())->query('SELECT 1'); // initialize connection statically
            $con = $GLOBALS['__AGROLINK_DB_CONNECTION'];

            if (!$con) {
                throw new RuntimeException("Database connection not found in GLOBALS");
            }

            $stm = $con->prepare($insertSql);
            if (!$stm->execute($params)) {
                $errorInfo = $stm->errorInfo();
                throw new RuntimeException("Delivery request insert failed: " . ($errorInfo[2] ?? 'Unknown PDO Error'));
            }

            return true;
        } catch (Exception $e) {
            $this->debugLog('Error creating delivery request: ' . $e->getMessage());
            throw new RuntimeException('Delivery Request SQL Error: ' . $e->getMessage());
        }
    }

    private function orderItemsHaveProductAddress(): bool
    {
        $dbModel = new CartModel();
        $result = $dbModel->query("SHOW COLUMNS FROM order_items LIKE 'product_full_address'");
        return is_array($result) && !empty($result);
    }

    private function getProductPickupAddressesForOrder(int $orderId, int $farmerId): string
    {
        if ($orderId <= 0 || $farmerId <= 0 || !$this->orderItemsHaveProductAddress()) {
            return '';
        }

        $dbModel = new CartModel();
        $rows = $dbModel->query(
            "SELECT DISTINCT TRIM(product_full_address) AS product_full_address
             FROM order_items
             WHERE order_id = :order_id
             AND farmer_id = :farmer_id
             AND product_full_address IS NOT NULL
             AND TRIM(product_full_address) <> ''",
            [
                'order_id' => $orderId,
                'farmer_id' => $farmerId,
            ]
        );

        if (!is_array($rows) || empty($rows)) {
            return '';
        }

        $addresses = [];
        foreach ($rows as $row) {
            $address = trim((string)($row->product_full_address ?? ''));
            if ($address !== '') {
                $addresses[] = $address;
            }
        }

        $addresses = array_values(array_unique($addresses));
        return implode(' | ', $addresses);
    }

    private function debugLog($message)
    {
        if (defined('DEBUG') && DEBUG) {
            error_log((string)$message);
        }
    }

    /**
     * Get the resolved district and town ID for a product/farmer combo.
     * Returns associative array with district_id, town_id, and district_name.
     */
    private function getFarmerProductLocation($farmerId, $product)
    {
        $districtId = !empty($product->district_id) ? (int)$product->district_id : null;
        $townId = !empty($product->town_id) ? (int)$product->town_id : null;
        $districtName = '';

        if (!$districtId) {
            $farmerProfile = $this->farmerModel->getProfileByUserId($farmerId);
            $districtName = $farmerProfile ? ($farmerProfile->district ?? $product->location ?? '') : ($product->location ?? '');
            $districtId = $this->findDistrictIdByName($districtName);
        } else {
            if ($this->shippingCalculator) {
                $districts = $this->shippingCalculator->getAllDistricts();
                foreach ($districts as $d) {
                    if ($d['id'] == $districtId) {
                        $districtName = $d['district_name'];
                        break;
                    }
                }
            }
        }

        if (!$townId) {
            $townId = $this->getTownIdByName($product->location ?? '', $districtId);
        }

        return [
            'district_id' => $districtId,
            'town_id' => $townId,
            'district_name' => $districtName
        ];
    }

    /**
     * Get towns by district name (AJAX)
     */
    public function getTownsByDistrictName()
    {
        header('Content-Type: application/json');

        $districtName = $_GET['district'] ?? '';
        $districtId = $this->findDistrictIdByName($districtName);

        if (!$districtId || !$this->shippingCalculator) {
            echo json_encode(['success' => false, 'towns' => []]);
            return;
        }

        $towns = $this->shippingCalculator->getTownsByDistrict($districtId);
        echo json_encode(['success' => true, 'towns' => $towns]);
    }
}
