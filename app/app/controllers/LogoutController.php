<?php
class LogoutController
{
    use Controller;

    public function index()
    {
        clearAuthSession();

        // Redirect to homepage
        redirect('home');
    }
}