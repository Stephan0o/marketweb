<?php
// ROUTER PHP - MANEJA URLs SIN EXTENSIÓN

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remover /marketWebAzure/ del path si está
$request_uri = str_replace('/marketWebAzure', '', $request_uri);

// Si es raíz, redirige a login
if ($request_uri === '/' || $request_uri === '') {
    header('Location: /public/login');
    exit();
}

if (preg_match('/^\/public\/(.+?)(\?.*)?$/', $request_uri, $matches)) {
    $file = __DIR__ . '/public/' . $matches[1] . '.php';
    if (file_exists($file)) {
        include $file;
        exit();
    }
}

if (preg_match('/^\/backend\/api\/(.+?)(\?.*)?$/', $request_uri, $matches)) {
    $file = __DIR__ . '/backend/api/' . $matches[1] . '.php';
    if (file_exists($file)) {
        include $file;
        exit();
    }
}

http_response_code(404);
echo "404 - Not Found: $request_uri";
exit();
?>