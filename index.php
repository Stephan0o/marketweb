<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/' || $path === '') {
    header('Location: /public/index.php');
    exit();
}

if (strpos($path, '/public/') === 0) {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        include $file;
        exit();
    }
}

if (strpos($path, '/backend/') === 0) {
    $file = __DIR__ . $path;
    if (is_file($file)) {
        include $file;
        exit();
    }
}

http_response_code(404);
echo "404 - Not Found";
exit();
?>