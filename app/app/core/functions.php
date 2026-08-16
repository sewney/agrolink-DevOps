<?php

function redirect($path)
{
    header('Location: ' . ROOT . '/' . $path);
    exit();
}

function esc($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function sessionValue($key, $default = null)
{
    return $_SESSION[$key] ?? $default;
}

function flash($key, $default = null)
{
    if (!isset($_SESSION[$key])) {
        return $default;
    }

    $value = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $value;
}

function show($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}