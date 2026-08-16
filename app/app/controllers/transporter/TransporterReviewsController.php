<?php

class TransporterReviewsController
{
    use Controller;

    public function index()
    {
        if (!requireRole('transporter')) {
            return;
        }

        $data = [];
        $data['pageTitle'] = 'Reviews';
        $data['activePage'] = 'reviews';
        $data['username'] = authUserName();
        $data['pageStyles'] = ['dashboard.css', 'reviews.css'];
        $data['pageScript'] = 'transporterReviews.js';
        $data['contentView'] = '../app/views/transporter/transporterReviews.view.php';

        $this->view('transporter/transporterSidebar', $data);
    }
}
