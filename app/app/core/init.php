<?php

spl_autoload_register(function ($classname) {
    // Try root level first
    $filename = "../app/models/" . ucfirst($classname) . ".php";
    if (file_exists($filename)) {
        require $filename;
        return;
    }

    // Try with Model suffix at root
    $modelFilename = "../app/models/" . ucfirst($classname) . "Model.php";
    if (file_exists($modelFilename)) {
        require $modelFilename;
        return;
    }

    // Search in role-based subdirectories (admin, buyer, farmer, transporter)
    $roles = ['admin', 'buyer', 'farmer', 'transporter'];
    foreach ($roles as $role) {
        $roleFile = "../app/models/{$role}/" . ucfirst($classname) . ".php";
        if (file_exists($roleFile)) {
            require $roleFile;
            return;
        }

        $roleModelFile = "../app/models/{$role}/" . ucfirst($classname) . "Model.php";
        if (file_exists($roleModelFile)) {
            require $roleModelFile;
            return;
        }
    }
});

require 'config.php';
require 'functions.php';
require 'AuthHelper.php';
require 'Database.php';
require 'Model.php';
require 'Controller.php';
require 'app.php';
