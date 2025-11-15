<?php
// ROUTER PHP - MANEJA REDIRECCIONES AUTOMÁTICAMENTE

// Interceptar header()
class HeaderInterceptor {
    public static function intercept() {
        ob_start();
        register_shutdown_function(['HeaderInterceptor', 'processHeaders']);
    }
    
    public static function processHeaders() {
        $headers = headers_list();
        foreach ($headers as $header) {
            if (strpos($header, 'Location:') === 0) {
                $location = trim(substr($header, 9));
                $location = preg_replace('/\.php(\?.*)?$/', '$1', $location);
                header('Location: ' . $location, true);
            }
        }
    }
}

// Iniciar interceptor
HeaderInterceptor::intercept();

// ROUTING
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$request_uri = str_replace('/marketWebAzure', '', $request_uri);

// Limpiar query string de la ruta
$path = strtok($request_uri, '?');
$query_string = $_SERVER['QUERY_STRING'] ?? '';

// Si es raíz, redirige a login
if ($path === '/' || $path === '') {
    include __DIR__ . '/public/index.php';
    exit();
}

// Buscar el archivo .php
$file = __DIR__ . $path . '.php';

// Si el archivo existe, incluirlo
if (file_exists($file)) {
    include $file;
    exit();
}

if (file_exists(__DIR__ . $path)) {
    include __DIR__ . $path;
    exit();
}

// Si nada funciona, error 404
http_response_code(404);
echo "404 - Not Found: $path";
exit();
?>