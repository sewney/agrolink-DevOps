<?php
class App
{
    private $controller = 'Home';
    private $method = 'index';

    private function splitURL()
    {
        $URL = $_GET['url'] ?? 'home';
        $URL = explode("/", trim($URL, "/"));
        return ($URL);
    }

    public function loadController()
    {

        $URL = $this->splitURL();

        //SELECT CONTROLLER - Try root level first
        $filename = "../app/controllers/" . ucfirst($URL[0]) . "Controller.php";
        if (file_exists($filename)) {
            require $filename;
            $this->controller = ucfirst($URL[0]) . 'Controller';
            unset($URL[0]);
        } else {
            // Try role-based subdirectories (admin, buyer, farmer, transporter)
            $roles = ['admin', 'buyer', 'farmer', 'transporter'];
            $found = false;

            foreach ($roles as $role) {
                $baseName = ucfirst($URL[0]);
                $roleController = "../app/controllers/{$role}/" . $baseName . "Controller.php";
                
                // On Windows, file_exists is case-insensitive, so check if file exists
                // We need to find the actual file name to get the correct class name
                $actualFile = null;
                if (file_exists($roleController)) {
                    $actualFile = $roleController;
                } else {
                    // Try to find file with different case (for camelCase like BuyerProfile)
                    $dir = "../app/controllers/{$role}/";
                    if (is_dir($dir)) {
                        $files = scandir($dir);
                        $searchPattern = strtolower($baseName . "Controller.php");
                        foreach ($files as $file) {
                            if (strtolower($file) === $searchPattern) {
                                $actualFile = $dir . $file;
                                break;
                            }
                        }
                    }
                }
                
                if ($actualFile && file_exists($actualFile)) {
                    require $actualFile;
                    // Extract actual class name from filename
                    $filename = basename($actualFile, '.php');
                    $this->controller = $filename;
                    $found = true;
                    unset($URL[0]);
                    break;
                }
            }

            if (!$found) {
                $filename = "../app/controllers/_404.php";
                require $filename;
                $this->controller = "_404";
            }
        }

        $controller = new $this->controller;

        //SELECT METHOD
        if (!empty($URL[1])) {
            if (method_exists($controller, $URL[1])) {
                $this->method = $URL[1];
                unset($URL[1]);
            }
        }
        call_user_func_array([$controller, $this->method], $URL);
    }
}
