<?php

class FarmerReviewsController
{
    use Controller;

    private $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        if (!hasRole('farmer')) {
            redirect('login');
            return;
        }

        $farmerId = authUserId();
        $reviews = $this->reviewModel->getReviewsByFarmer($farmerId);

        $data = [
            'pageTitle' => 'My Reviews',
            'activePage' => 'reviews',
            'pageStyles' => ['reviews.css'],
            'reviews' => $reviews,
            'contentView' => '../app/views/farmer/reviews.view.php'
        ];

        $this->view('farmer/farmerSidebar', $data);
    }

    public function reply()
    {
        header('Content-Type: application/json');

        if (!hasRole('farmer')) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $reviewId = $_POST['review_id'] ?? null;
        $reply = trim($_POST['reply'] ?? '');

        if (!$reviewId || !$reply) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Review ID and reply are required']);
            exit;
        }

        $review = $this->reviewModel->getReviewById($reviewId);
        if (!$review || (int)$review->farmer_id !== (int)authUserId()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You are not allowed to reply to this review']);
            exit;
        }

        if ($this->reviewModel->replyToReview($reviewId, $reply)) {
            echo json_encode(['success' => true, 'message' => 'Reply posted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to post reply']);
        }
        exit;
    }
}
