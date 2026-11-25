<?php

// Only assets and generated media are public in the fixture document root.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = realpath(__DIR__.$path);
if ($file && (str_starts_with($file, __DIR__.'/assets/') || str_starts_with($file, __DIR__.'/media/')) && is_file($file) && ! str_ends_with(strtolower($file), '.php')) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__.'/index.php';
