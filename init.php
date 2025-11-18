<?php
// INICIALIZACIÓN - COMPATIBLE CON LOCALHOST Y RENDER

if (defined('APP_INITIALIZED')) {
    $conn = $GLOBALS['conn'];
    return;
}

// CARGAR AUTOLOADER

if (!function_exists('spl_autoload_functions') || !in_array('Composer\Autoload\ClassLoader', array_map('get_class', spl_autoload_functions()))) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// CARGAR VARIABLES DE ENTORNO

$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// CACHEAR EN GLOBALES

if (!isset($GLOBALS['DB_HOST'])) {
    $GLOBALS['DB_HOST'] = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
    $GLOBALS['DB_USER'] = $_ENV['DB_USER'] ?? getenv('DB_USER');
    $GLOBALS['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
    $GLOBALS['DB_NAME'] = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
    $GLOBALS['GEMINI_KEY_1'] = $_ENV['GEMINI_API_KEY_1'] ?? getenv('GEMINI_API_KEY_1');
    $GLOBALS['GEMINI_KEY_2'] = $_ENV['GEMINI_API_KEY_2'] ?? getenv('GEMINI_API_KEY_2');
    $GLOBALS['GEMINI_KEY_3'] = $_ENV['GEMINI_API_KEY_3'] ?? getenv('GEMINI_API_KEY_3');
    $GLOBALS['APP_ENV'] = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'development';
}

// CONECTAR A BASE DE DATOS

if (!isset($GLOBALS['conn']) || $GLOBALS['conn'] === null) {
    $GLOBALS['conn'] = new mysqli(
        $GLOBALS['DB_HOST'],
        $GLOBALS['DB_USER'],
        $GLOBALS['DB_PASSWORD'],
        $GLOBALS['DB_NAME']
    );
    
    if ($GLOBALS['conn']->connect_error) {
        die('Error BD: ' . $GLOBALS['conn']->connect_error);
    }
    
    $GLOBALS['conn']->set_charset("utf8mb4");
}

$conn = $GLOBALS['conn'];

// DEFINIR CONSTANTES

if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? '');
    define('DB_USER', $_ENV['DB_USER'] ?? '');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? '');
    define('GEMINI_API_KEY_1', $_ENV['GEMINI_API_KEY_1'] ?? '');
    define('GEMINI_API_KEY_2', $_ENV['GEMINI_API_KEY_2'] ?? '');
    define('GEMINI_API_KEY_3', $_ENV['GEMINI_API_KEY_3'] ?? '');
    define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
    define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'MarketWeb');
    define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY'] ?? '');
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
}

// CONFIGURAR ERRORES

if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

define('APP_INITIALIZED', true);

?>