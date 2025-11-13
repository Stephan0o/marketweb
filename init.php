<?php
// ============================================
// INICIALIZACIÓN ULTRA-OPTIMIZADA
// ============================================

// Verificar si ya está cargado TODO
if (defined('APP_INITIALIZED')) {
    $conn = $GLOBALS['conn'];
    return;
}

// ============================================
// CARGAR AUTOLOADER SOLO UNA VEZ
// ============================================

if (!function_exists('spl_autoload_functions') || !in_array('Composer\Autoload\ClassLoader', array_map('get_class', spl_autoload_functions()))) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ============================================
// CARGAR .env SOLO UNA VEZ (CACHEAR EN VARIABLES GLOBALES)
// ============================================

if (!isset($GLOBALS['ENV_LOADED'])) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    // Cachear en variables globales
    $GLOBALS['ENV_LOADED'] = true;
    $GLOBALS['DB_HOST'] = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
    $GLOBALS['DB_USER'] = $_ENV['DB_USER'] ?? getenv('DB_USER');
    $GLOBALS['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');
    $GLOBALS['DB_NAME'] = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
    $GLOBALS['GEMINI_KEY'] = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY');
    $GLOBALS['APP_ENV'] = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'development';
}

// ============================================
// CREAR CONEXIÓN A BD (SINGLETON)
// ============================================

if (!isset($GLOBALS['conn']) || $GLOBALS['conn'] === null) {
    $GLOBALS['conn'] = new mysqli(
        $GLOBALS['DB_HOST'],
        $GLOBALS['DB_USER'],
        $GLOBALS['DB_PASSWORD'],
        $GLOBALS['DB_NAME']
    );
    
    if ($GLOBALS['conn']->connect_error) {
        die('❌ Error BD: ' . $GLOBALS['conn']->connect_error);
    }
    
    $GLOBALS['conn']->set_charset("utf8mb4");
}

// ============================================
// VARIABLES LOCALES PARA ACCESO FÁCIL
// ============================================

$conn = $GLOBALS['conn'];
$app_env = $GLOBALS['APP_ENV'];

// ============================================
// DEFINIR CONSTANTES (PARA COMPATIBILIDAD)
// ============================================

if (!defined('DB_HOST')) {
    define('DB_HOST', $GLOBALS['DB_HOST']);
    define('DB_USER', $GLOBALS['DB_USER']);
    define('DB_PASSWORD', $GLOBALS['DB_PASSWORD']);
    define('DB_NAME', $GLOBALS['DB_NAME']);
    define('GEMINI_API_KEY', $GLOBALS['GEMINI_KEY']);
    define('APP_ENV', $GLOBALS['APP_ENV']);
    
    // Constantes para Email (PHPMailer)
    define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
    define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
    define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
    define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
    define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'MarketWeb');
    define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
}

// ============================================
// CONFIGURAR ERRORES SEGÚN AMBIENTE
// ============================================

if ($app_env === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// ============================================
// MARCAR COMO INICIALIZADO
// ============================================

define('APP_INITIALIZED', true);

?>