<?php

class FarmerProductsController
{
    use Controller;

    protected $productModel;
    protected $shippingCalculator;

    public function __construct()
    {
        $this->productModel = new ProductsModel();

        // Initialize shipping calculator for location helpers
        try {
            $pdo = new PDO("mysql:host=" . DBHOST . ";dbname=" . DBNAME, DBUSER, DBPASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->shippingCalculator = new SimpleShippingCalculator($pdo);
        } catch (PDOException $e) {
            error_log("Shipping calculator initialization error: " . $e->getMessage());
            $this->shippingCalculator = null;
        }
    }

    /**
     * Absolute path to product image directory under public assets.
     */
    private function getProductImageDirectory(): string
    {
        $publicPath = realpath(__DIR__ . '/../../../public');
        if ($publicPath === false) {
            throw new RuntimeException('Public directory not found');
        }

        return $publicPath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR;
    }

    /**
     * Farmer products page (renders shared layout)
     */
    public function index()
    {
        if (!hasRole('farmer')) {
            return redirect('login');
        }

        $data = [
            'pageTitle'   => 'My Products',
            'activePage'  => 'products',
            'pageStyles'  => ['products.css'],
            'contentView' => '../app/views/farmer/farmerProductsContent.view.php',
            'pageScript'  => 'products.js'
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    /**
     * Create a new product (Farmer only)
     */
    public function create()
    {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');

        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'You must be logged in to add products'
            ]);
            exit;
        }

        $userRole = authUserRole();

        if ($userRole !== 'farmer') {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Only farmers can add products',
                'debug' => [
                    'userRole' => $userRole,
                    'userRoleRaw' => authUserRole(),
                    'expectedRole' => 'farmer'
                ]
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        try {
            $farmer_id = authUserId();

            $errors = [];

            $category = strtolower(trim($_POST['category'] ?? ''));
            $name = trim($_POST['name'] ?? '');
            $productMasterId = !empty($_POST['product_master_id']) ? (int)$_POST['product_master_id'] : null;
            $price = trim($_POST['price'] ?? '');
            $quantity = trim($_POST['quantity'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $fullAddress = trim($_POST['full_address'] ?? '');
            $listing_date = trim($_POST['listing_date'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (empty($category)) {
                $errors['category'] = 'Category is required';
            }

            // If product_master_id provided, fetch standardized name from crop_volume_factors
            if ($productMasterId) {
                try {
                    $pdo = new PDO("mysql:host=" . DBHOST . ";dbname=" . DBNAME, DBUSER, DBPASS);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $stmt = $pdo->prepare("SELECT crop_name FROM crop_volume_factors WHERE id = ?");
                    $stmt->execute([$productMasterId]);
                    $cropData = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($cropData) {
                        $name = $cropData['crop_name'];
                    } else {
                        $errors['product_master_id'] = 'Invalid product selected';
                    }
                } catch (PDOException $e) {
                    error_log("Product lookup error: " . $e->getMessage());
                    $errors['product_master_id'] = 'Database error';
                }
            }

            if (empty($name)) {
                $errors['name'] = 'Product name is required';
            } elseif (strlen($name) < 3) {
                $errors['name'] = 'Product name must be at least 3 characters';
            } elseif (strlen($name) > 100) {
                $errors['name'] = 'Product name is too long (max 100 characters)';
            }
            if (empty($price)) {
                $errors['price'] = 'Price is required';
            } elseif (!is_numeric($price) || $price <= 0) {
                $errors['price'] = 'Price must be a positive number';
            }
            if (empty($quantity)) {
                $errors['quantity'] = 'Quantity is required';
            } elseif (!is_numeric($quantity) || $quantity < 10) {
                $errors['quantity'] = 'Minimum quantity is 10kg';
            }
            if (empty($location) || $location === 'auto') {
                // If using new dropdowns, construct location string.
                // Some districts may have no towns; in that case district-only location is valid.
                $districtId = $_POST['district_id'] ?? '';
                $townId = $_POST['town_id'] ?? '';

                if (!empty($districtId) && $this->shippingCalculator) {
                    $districts = $this->shippingCalculator->getAllDistricts();
                    $districtName = '';
                    foreach ($districts as $d) {
                        if ($d['id'] == $districtId) {
                            $districtName = $d['district_name'];
                            break;
                        }
                    }

                    $townName = '';
                    if (!empty($townId)) {
                        $towns = $this->shippingCalculator->getTownsByDistrict($districtId);
                        foreach ($towns as $t) {
                            if ($t['id'] == $townId) {
                                $townName = $t['town_name'];
                                break;
                            }
                        }
                    }

                    if ($districtName) {
                        $location = $townName ? ($townName . ', ' . $districtName) : $districtName;
                    }
                }

                if (empty($location)) {
                    $location = authUserLocation();
                }

                if (empty($location)) {
                    $errors['location'] = 'Location is required';
                }
            }
            if ($fullAddress === '') {
                $errors['full_address'] = 'Product full address is required';
            } elseif (strlen($fullAddress) < 5) {
                $errors['full_address'] = 'Product full address is too short';
            } elseif (strlen($fullAddress) > 200) {
                $errors['full_address'] = 'Product full address is too long (max 500 characters)';
            }
            if (empty($listing_date)) {
                $errors['listing_date'] = 'Listing date is required';
            } else {
                $date = DateTime::createFromFormat('Y-m-d', $listing_date);
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                if (!$date || $date < $today) {
                    $errors['listing_date'] = 'Listing date cannot be in the past';
                }
            }

            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $errors['image'] = 'Invalid image format. Allowed: ' . implode(', ', $allowed);
                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $errors['image'] = 'Image size must be less than 5MB';
                } else {
                    $imageName = uniqid('product_') . '.' . $ext;
                    $uploadPath = $this->getProductImageDirectory();
                    if (!is_dir($uploadPath)) {
                        if (!mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
                            $errors['image'] = 'Failed to create upload directory';
                            $imageName = null;
                        }
                    }
                    if ($imageName !== null) {
                        if (!is_writable($uploadPath)) {
                            @chmod($uploadPath, 0777);
                        }
                        if (!is_writable($uploadPath)) {
                            $errors['image'] = 'Upload directory is not writable';
                            $imageName = null;
                        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $imageName)) {
                            $errors['image'] = 'Failed to upload image';
                            $imageName = null;
                        }
                    }
                }
            }

            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $errors
                ]);
                exit;
            }

            $data = [
                'farmer_id'         => $farmer_id,
                'category'          => $category,
                'name'              => $name,
                'product_master_id' => $productMasterId,
                'price'             => (float)$price,
                'quantity'          => (int)$quantity,
                'location'          => $location,
                'full_address'      => $fullAddress,
                'listing_date'      => $listing_date,
                'description'       => $description,
                'image'             => $imageName,
                'district_id'       => !empty($_POST['district_id']) ? (int)$_POST['district_id'] : null,
                'town_id'           => !empty($_POST['town_id']) ? (int)$_POST['town_id'] : null
            ];

            $result = $this->productModel->create($data);

            if ($result) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added successfully',
                    'product_id' => $result
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to add product to database'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    // Return farmer's products (JSON)
    public function farmerList()
    {
        header('Content-Type: application/json');

        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            return;
        }

        if (!hasRole('farmer')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Not a farmer']);
            return;
        }

        $farmerId = (int)authUserId();
        $items = $this->productModel->getByFarmer($farmerId);
        echo json_encode(['success' => true, 'products' => $items ?: []]);
    }

    // Get all districts
    public function getDistricts()
    {
        header('Content-Type: application/json');
        if (!$this->shippingCalculator) {
            echo json_encode(['success' => false, 'error' => 'Calculator not initialized']);
            return;
        }
        $districts = $this->shippingCalculator->getAllDistricts();
        echo json_encode(['success' => true, 'districts' => $districts]);
    }

    // Get towns by district
    public function getTowns()
    {
        header('Content-Type: application/json');
        $districtId = $_GET['district_id'] ?? 0;
        if (!$this->shippingCalculator || !$districtId) {
            echo json_encode(['success' => false, 'towns' => []]);
            return;
        }
        $towns = $this->shippingCalculator->getTownsByDistrict($districtId);
        echo json_encode(['success' => true, 'towns' => $towns]);
    }

    // Get all product categories
    public function getCategories()
    {
        header('Content-Type: application/json');
        if (!$this->shippingCalculator) {
            echo json_encode(['success' => false, 'error' => 'Calculator not initialized']);
            return;
        }

        try {
            $pdo = new PDO("mysql:host=" . DBHOST . ";dbname=" . DBNAME, DBUSER, DBPASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->query(
                "SELECT DISTINCT LOWER(TRIM(category)) AS category
                 FROM crop_volume_factors
                 WHERE category IS NOT NULL
                 AND TRIM(category) <> ''
                 ORDER BY category"
            );
            $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo json_encode(['success' => true, 'categories' => $categories]);
        } catch (PDOException $e) {
            error_log("Get categories error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    }

    // Get products by category
    public function getProductsByCategory()
    {
        header('Content-Type: application/json');
        $category = $_GET['category'] ?? '';

        if (!$category) {
            echo json_encode(['success' => false, 'products' => []]);
            return;
        }

        try {
            $pdo = new PDO("mysql:host=" . DBHOST . ";dbname=" . DBNAME, DBUSER, DBPASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare(
                "SELECT id, crop_name, volume_factor
                 FROM crop_volume_factors
                 WHERE LOWER(TRIM(category)) = LOWER(TRIM(?))
                 ORDER BY crop_name"
            );
            $stmt->execute([$category]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'products' => $products]);
        } catch (PDOException $e) {
            error_log("Get products error: " . $e->getMessage());
            echo json_encode(['success' => false, 'products' => []]);
        }
    }

    // Buyer products list (JSON) - optional, kept for compatibility
    public function buyerList()
    {
        header('Content-Type: application/json');
        if (!hasRole('buyer')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied']);
            return;
        }

        $conditions = [];
        if (!empty($_GET['category'])) {
            $conditions['category'] = $_GET['category'];
        }
        if (!empty($_GET['location'])) {
            $conditions['location'] = $_GET['location'];
        }
        if (!empty($_GET['min_price'])) {
            $conditions['min_price'] = $_GET['min_price'];
        }
        if (!empty($_GET['max_price'])) {
            $conditions['max_price'] = $_GET['max_price'];
        }

        $products = $this->productModel->getWithFarmerDetails($conditions);
        echo json_encode(['success' => true, 'products' => $products]);
    }

    public function update($id = null)
    {
        header('Content-Type: application/json');
        if (!$this->requireFarmer()) return;
        $id = (int)($id ?? ($_POST['id'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $current = $this->productModel->getById($id);
        if (!$current || (int)$current->farmer_id !== (int)authUserId()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $category = strtolower(trim($_POST['category'] ?? ''));
        $price = $_POST['price'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $fullAddress = trim($_POST['full_address'] ?? '');
        $districtId = $_POST['district_id'] ?? '';
        $townId = $_POST['town_id'] ?? '';

        // Construct location if IDs provided
        if ((empty($location) || $location === 'auto') && !empty($districtId) && $this->shippingCalculator) {
            $districts = $this->shippingCalculator->getAllDistricts();
            $districtName = '';
            foreach ($districts as $d) {
                if ($d['id'] == $districtId) {
                    $districtName = $d['district_name'];
                    break;
                }
            }

            $townName = '';
            if (!empty($townId)) {
                $towns = $this->shippingCalculator->getTownsByDistrict($districtId);
                foreach ($towns as $t) {
                    if ($t['id'] == $townId) {
                        $townName = $t['town_name'];
                        break;
                    }
                }
            }

            if ($districtName) {
                $location = $townName ? ($townName . ', ' . $districtName) : $districtName;
            }
        }
        $listing_date = trim($_POST['listing_date'] ?? '');

        $errors = [];
        if ($name === '') $errors['name'] = 'Name is required';
        if ($category === '') $errors['category'] = 'Category is required';
        if ($price === '' || !is_numeric($price) || $price < 0) $errors['price'] = 'Price is required';
        if ($quantity === '' || !is_numeric($quantity) || (int)$quantity < 0) $errors['quantity'] = 'Quantity is required';
        if ($location === '') $errors['location'] = 'Location is required';
        if ($fullAddress === '') {
            $errors['full_address'] = 'Product full address is required';
        } elseif (strlen($fullAddress) < 5) {
            $errors['full_address'] = 'Product full address is too short';
        } elseif (strlen($fullAddress) > 200) {
            $errors['full_address'] = 'Product full address is too long (max 500 characters)';
        }
        if ($listing_date === '') {
            $errors['listing_date'] = 'Listing date is required';
        } else {
            $date = DateTime::createFromFormat('Y-m-d', $listing_date);
            $today = new DateTime();
            $today->setTime(0, 0, 0);
            if (!$date || $date < $today) {
                $errors['listing_date'] = 'Listing date cannot be in the past';
            }
        }

        $newImageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $errors['image'] = 'Invalid image format. Allowed: ' . implode(', ', $allowed);
                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $errors['image'] = 'Image size must be less than 5MB';
                } else {
                    $newImageName = uniqid('product_') . '.' . $ext;
                    $uploadPath = $this->getProductImageDirectory();
                    if (!is_dir($uploadPath)) {
                        if (!mkdir($uploadPath, 0775, true) && !is_dir($uploadPath)) {
                            $errors['image'] = 'Failed to create upload directory';
                            $newImageName = null;
                        }
                    }
                    if ($newImageName !== null) {
                        if (!is_writable($uploadPath)) {
                            @chmod($uploadPath, 0777);
                        }
                        if (!is_writable($uploadPath)) {
                            $errors['image'] = 'Upload directory is not writable';
                            $newImageName = null;
                        } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath . $newImageName)) {
                            $errors['image'] = 'Failed to upload image';
                            $newImageName = null;
                        }
                    }
                }
            } else {
                $errors['image'] = 'File upload failed';
            }
        }

        if (!empty($errors)) {
            http_response_code(422);
            echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
            return;
        }

        $payload = [
            'name'         => $name,
            'category'     => $category,
            'price'        => (float)$price,
            'quantity'     => (int)$quantity,
            'location'     => $location,
            'full_address' => $fullAddress,
            'listing_date' => $listing_date,
            'district_id'  => !empty($districtId) ? (int)$districtId : null,
            'town_id'      => !empty($townId) ? (int)$townId : null
        ];
        if ($newImageName) {
            $payload['image'] = $newImageName;
        }

        $ok = $this->productModel->updateByFarmer($id, (int)authUserId(), $payload);

        if ($ok) {
            if ($newImageName && !empty($current->image)) {
                $oldPath = $this->getProductImageDirectory() . $current->image;
                if (is_file($oldPath)) @unlink($oldPath);
            }
            echo json_encode(['success' => true, 'message' => 'Product updated']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update product']);
        }
    }

    public function delete($id = null)
    {
        header('Content-Type: application/json');
        if (!$this->requireFarmer()) return;
        $id = (int)($id ?? ($_POST['id'] ?? 0));

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid id']);
            return;
        }

        $product = $this->productModel->getById($id);
        if (!$product || (int)$product->farmer_id !== (int)authUserId()) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $ongoing = $this->productModel->countOngoingOrders($id, (int)authUserId());
        if ($ongoing > 0) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Cannot delete this product while it has ' . $ongoing . ' ongoing order' . ($ongoing === 1 ? '' : 's') . '. Wait until they are delivered or cancelled.'
            ]);
            return;
        }

        $ok = $this->productModel->deleteByFarmer($id, (int)authUserId());
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Product deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete product']);
        }
    }

    public function show($id)
    {
        header('Content-Type: application/json');
        $item = $this->productModel->getById((int)$id);
        if (!$item) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        echo json_encode(['success' => true, 'product' => $item]);
    }

    private function requireFarmer()
    {
        if (!isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return false;
        }
        if (!hasRole('farmer')) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return false;
        }
        return true;
    }

    private function validate($in)
    {
        $errors = [];
        if (empty($in['name'])) $errors['name'] = 'Name is required';
        if (!isset($in['price']) || $in['price'] === '' || $in['price'] < 0) $errors['price'] = 'Price is required';
        if (!isset($in['quantity']) || !is_numeric($in['quantity']) || (int)$in['quantity'] < 0) $errors['quantity'] = 'Quantity is required';
        return $errors;
    }
}
