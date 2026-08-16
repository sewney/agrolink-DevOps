<?php

class BuyerProductsController
{
    use Controller;

    private $productModel;
    private $cartModel;
    private $wishlistModel;

    public function __construct()
    {
        $this->productModel = new ProductsModel();
        $this->cartModel = new CartModel();
        $this->wishlistModel = new WishlistModel();
    }

    public function index()
    {
        // Check if user is logged in and is a buyer
        if (!hasRole('buyer')) {
            redirect('login');
            return;
        }

        $user_id = authUserId();
        $cartItemCount = $this->cartModel->getCartItemCount($user_id);

        // Fetch all available products with farmer details
        $products = $this->productModel->getWithFarmerDetails();
        $products = $this->attachDisplayDates($products ?: []);
        $wishlistItems = $this->wishlistModel->getByUserId($user_id);

        $data = [
            'pageTitle' => 'Browse Products',
            'activePage' => 'products',
            'username' => authUserName(),
            'cartItemCount' => $cartItemCount,
            'products' => $products ?: [],
            'wishlistItems' => $wishlistItems ?: [],
            'pageStyles' => 'products.css',
            'pageScript' => 'buyerDashboard.js',
            'contentView' => 'buyer/products.view.php'
        ];

        // Load the view through main layout
        $this->view('buyer/buyerSidebar', $data);
    }

    private function attachDisplayDates(array $products): array
    {
        foreach ($products as $product) {
            $baseDate = null;

            if (!empty($product->listing_date)) {
                $baseDate = strtotime($product->listing_date);
            } elseif (!empty($product->created_at)) {
                $baseDate = strtotime($product->created_at);
            }

            if (!$baseDate) {
                $baseDate = time();
            }

            $shelfLifeDays = $this->getShelfLifeDays($product->category ?? 'other');
            $product->display_added_date = date('M d, Y', $baseDate);
            $product->display_best_use_date = date('M d, Y', strtotime("+{$shelfLifeDays} days", $baseDate));
        }

        return $products;
    }

    private function getShelfLifeDays(string $category): int
    {
        $map = [
            'leafy' => 3,
            'fruits' => 5,
            'vegetables' => 4,
            'yams' => 21,
            'spices' => 21,
            'legumes' => 21,
            'other' => 10,
            'cereals' => 30,
        ];

        $key = strtolower(trim($category));
        return $map[$key] ?? 10;
    }
}
