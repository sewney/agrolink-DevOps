<?php

class CartController
{
    use Controller;

    protected $cartModel;
    protected $productModel;

    public function __construct()
    {
        $this->cartModel = new CartModel();
        $this->productModel = new ProductsModel();
    }

    /**
     * Display cart page
     */
    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $user_id = authUserId();

        // Clear any orphaned Buy Now sessions when explicitly viewing the standard cart
        if (isset($_SESSION['buy_now_product_id'])) {
            unset($_SESSION['buy_now_product_id']);
        }

        // Get cart items
        $cartItems = $this->cartModel->getCartByUserId($user_id);
        
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
            } else {
                $item->available_quantity = 0;
            }
        }
        
        $cartItemCount = $this->cartModel->getCartItemCount($user_id);
        $cartTotal = $this->cartModel->getCartTotal($user_id);

        $data = [
            'cartItems' => $cartItems ?: [],
            'cartItemCount' => $cartItemCount,
            'cartTotal' => $cartTotal,
            'pageTitle' => 'Shopping Cart',
            'activePage' => 'cart',
            'pageStyles' => 'cart.css',
            'contentView' => 'buyer/cart.view.php'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Add item to cart (AJAX)
     */
    public function add()
    {
        header('Content-Type: application/json');
        if (!$this->requireBuyer()) exit;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $user_id = authUserId();
        $data = [
            'user_id' => $user_id,
            'product_id' => $_POST['product_id'] ?? null,
            'product_name' => trim($_POST['product_name'] ?? ''),
            'product_price' => (float)($_POST['product_price'] ?? 0),
            'quantity' => (int)($_POST['quantity'] ?? 1),
            'product_image' => trim($_POST['product_image'] ?? '🌱'),
            'farmer_name' => '',
            'farmer_location' => ''
        ];

        if (!$data['product_id']) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            exit;
        }

        try {
            // Check product availability before adding/updating
            $product = $this->productModel->getById($data['product_id']);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            $availableQuantity = $product->quantity ?? 0;
            $data['farmer_name'] = trim((string)($product->farmer_name ?? ''));
            $data['farmer_location'] = trim((string)($product->location ?? ''));
            
            // Check if product already exists in cart
            $existingItem = $this->cartModel->getCartItem($user_id, $data['product_id']);

            if ($existingItem) {
                // Update quantity - check if total doesn't exceed available
                $newQuantity = $existingItem->quantity + $data['quantity'];
                if ($newQuantity > $availableQuantity) {
                    http_response_code(422);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Only {$availableQuantity} kg available. Cannot add more."
                    ]);
                    exit;
                }
                
                $updated = $this->cartModel->updateQuantity($user_id, $data['product_id'], $newQuantity);

                if ($updated) {
                    $cartItemCount = $this->cartModel->getCartItemCount($user_id);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product quantity updated in cart',
                        'cartItemCount' => $cartItemCount
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
                }
            } else {
                // Add new item - check quantity doesn't exceed available
                if ($data['quantity'] > $availableQuantity) {
                    http_response_code(422);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Only {$availableQuantity} kg available. Please select a lower quantity."
                    ]);
                    exit;
                }
                
                // Add new item
                $added = $this->cartModel->addToCart($data);

                if ($added) {
                    $cartItemCount = $this->cartModel->getCartItemCount($user_id);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product added to cart successfully',
                        'cartItemCount' => $cartItemCount
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to add product to cart']);
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Update quantity (AJAX)
     */
    public function update($id = null)
    {
        header('Content-Type: application/json');
        if (!$this->requireBuyer()) exit;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $user_id = authUserId();
        
        // Handle both JSON and form-encoded data
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $data = $_POST;
        }
        
        $product_id = $id ?? ($data['product_id'] ?? null);
        $quantity = (int)($data['quantity'] ?? 0);

        if (!$product_id || $quantity < 1) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
            exit;
        }

        try {
            // Check product availability before updating
            $product = $this->productModel->getById($product_id);
            if (!$product) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            $availableQuantity = $product->quantity ?? 0;
            
            // Validate quantity doesn't exceed available stock
            if ($quantity > $availableQuantity) {
                http_response_code(422);
                echo json_encode([
                    'success' => false, 
                    'message' => "Only {$availableQuantity} kg available. Cannot select more than available stock."
                ]);
                exit;
            }
            
            $updated = $this->cartModel->updateQuantity($user_id, $product_id, $quantity);

            if ($updated) {
                $cartItemCount = $this->cartModel->getCartItemCount($user_id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart updated successfully',
                    'cartItemCount' => $cartItemCount
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Remove item (AJAX)
     */
    public function remove($id = null)
    {
        header('Content-Type: application/json');
        if (!$this->requireBuyer()) exit;

        $user_id = authUserId();
        
        // Handle both JSON and form-encoded data
        $data = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $data = $_POST;
        }
        
        $product_id = $id ?? ($data['product_id'] ?? null);

        if (!$product_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            exit;
        }

        try {
            $removed = $this->cartModel->removeFromCart($user_id, $product_id);

            if ($removed) {
                $cartItemCount = $this->cartModel->getCartItemCount($user_id);
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart',
                    'cartItemCount' => $cartItemCount
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to remove']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Clear cart (AJAX)
     */
    public function clear()
    {
        header('Content-Type: application/json');
        if (!$this->requireBuyer()) exit;

        try {
            $user_id = authUserId();
            $cleared = $this->cartModel->clearCart($user_id);

            if ($cleared) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared successfully',
                    'cartItemCount' => 0
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to clear cart']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Get cart data (AJAX)
     */
    public function getData()
    {
        header('Content-Type: application/json');
        if (!$this->requireBuyer()) exit;

        try {
            $user_id = authUserId();
            $cartItemCount = $this->cartModel->getCartItemCount($user_id);
            $cartTotal = $this->cartModel->getCartTotal($user_id);

            echo json_encode([
                'success' => true,
                'cartItemCount' => $cartItemCount,
                'cartTotal' => $cartTotal
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // Helper
    private function requireBuyer()
    {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return false;
        }
        if (!hasRole('buyer')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return false;
        }
        return true;
    }
}
