<?php
class AdminDashboard {
    use Model;
    protected $table = 'users'; // or whatever table you need
    
    public function index() {
        // Your admin-specific queries here
        return $this->query("SELECT * FROM users WHERE role = 'admin'");
    }
}