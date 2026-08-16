<?php
    // Compute ROOT dynamically and robustly for Apache rewrite + XAMPP htdocs.
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    $basePath = '';
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';

    // Prefer deriving from request path when it contains /public.
    if ($requestPath !== '') {
        $requestNorm = str_replace('\\', '/', $requestPath);
        if (preg_match('#^(.*?/public)(?:/|$)#i', $requestNorm, $m) && !empty($m[1])) {
            $basePath = '/' . trim($m[1], '/');
        }
    }

    $publicDir = realpath(__DIR__ . '/../../public');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;

    if ($basePath === '' && $publicDir && $documentRoot) {
        $publicNorm = str_replace('\\', '/', $publicDir);
        $docNorm = rtrim(str_replace('\\', '/', $documentRoot), '/');

        if (strpos($publicNorm, $docNorm) === 0) {
            $relative = substr($publicNorm, strlen($docNorm));
            $basePath = '/' . trim((string)$relative, '/');
        }
    }

    if ($basePath === '' || $basePath === '/') {
        // Fallback when document-root mapping is unavailable.
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $basePath = '/' . trim($scriptDir, '/');
    }

    $rootUrl = rtrim($protocol . '://' . $host . rtrim($basePath, '/'), '/');
    define('ROOT', $rootUrl);

    define('DBHOST', getenv('DBHOST') ?: 'localhost');
    define('DBNAME', getenv('DBNAME') ?: 'agrolink');
    define('DBUSER', getenv('DBUSER') ?: 'root');
    define('DBPASS', getenv('DBPASS') ?: '');   

    define('APP_NAME', "My Website");
    define('APP_DESC', "tHIS IS MY Website");
    
    //true shows errors
    define('DEBUG', true);