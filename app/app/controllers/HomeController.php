<?php

class HomeController
{
    use Controller;

    public function index($a = '', $b = '', $c = '')
    {
        if (redirectIfLoggedIn()) {
            return;
        }

        $data = [];
        $this->view('home', $data);
    }
}