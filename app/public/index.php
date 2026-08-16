<?php
session_start();

require "../app/core/init.php";

DEBUG ? ini_set('display_errors', 1) : ini_set('display_errors', 0);

define('UPLOAD_DIR', dirname(__DIR__) . '/public/');


$app = new App;
$app->loadController();
