<?php

class WishlistController
{
    use Controller;

    private $wishlistModel;

    public function __construct()
    {
        $this->wishlistModel = new WishlistModel();
    }

    private function requireBuyer()
    {
        if (!hasRole('buyer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return false;
        }
        return true;
    }

    /**
     * Display wishlist page (HTML view)
     */
    public function index()
    {
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $user_id = authUserId();
        $wishlistItems = $this->wishlistModel->getByUserId($user_id);

        $data = [
            'pageTitle' => 'My Wishlist',
            'activePage' => 'wishlist',
            'username' => authUserName(),
            'wishlistItems' => $wishlistItems ?: [],
            'pageStyles' => ['products.css', 'wishlist.css'],
            'pageScript' => 'buyerDashboard.js',
            'contentView' => 'buyer/wishlist.view.php'
        ];

        $this->view('buyer/buyerSidebar', $data);
    }

    /**
     * Get wishlist items as JSON (AJAX endpoint)
     */
    public function get()
    {
        header('Content-Type: application/json');

        if (!$this->requireBuyer()) {
            return;
        }

        $user_id = authUserId();
        $items = $this->wishlistModel->getByUserId($user_id);

        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    }

    public function add()
    {
        header('Content-Type: application/json');

        if (!$this->requireBuyer()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        if ($product_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid product']);
            return;
        }

        $user_id = authUserId();
        $result = $this->wishlistModel->add($user_id, $product_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Product added to wishlist',
                'wishlistCount' => $this->wishlistModel->countByUser($user_id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to add to wishlist']);
        }
    }

    public function remove($product_id = null)
    {
        header('Content-Type: application/json');

        if (!$this->requireBuyer()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        if (!$product_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Product required']);
            return;
        }

        $user_id = authUserId();
        $result = $this->wishlistModel->remove($user_id, (int)$product_id);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Product removed from wishlist',
                'wishlistCount' => $this->wishlistModel->countByUser($user_id)
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to remove product']);
        }
    }

    public function clear()
    {
        header('Content-Type: application/json');

        if (!$this->requireBuyer()) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            return;
        }

        $user_id = authUserId();
        $this->wishlistModel->clear($user_id);

        echo json_encode(['success' => true, 'message' => 'Wishlist cleared']);
    }
}

