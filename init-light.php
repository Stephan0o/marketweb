<?php
// ============================================
// CARGAR VARIABLES DE ENTORNO (SIN VENDOR)
// ============================================

// Leer .env manualmente (sin phpdotenv)
if (!isset($GLOBALS['ENV_CACHED'])) {
    $env_file = __DIR__ . '/.env';
    
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
        }
    }
    
    $GLOBALS['ENV_CACHED'] = true;
}

// ============================================
// CACHEAR EN GLOBALES
// ============================================

if (!isset($GLOBALS['DB_HOST'])) {
    $GLOBALS['DB_HOST'] = $_ENV['DB_HOST'] ?? '';
    $GLOBALS['DB_USER'] = $_ENV['DB_USER'] ?? '';
    $GLOBALS['DB_PASSWORD'] = $_ENV['DB_PASSWORD'] ?? '';
    $GLOBALS['DB_NAME'] = $_ENV['DB_NAME'] ?? '';
}

// ============================================
// CONECTAR A BD
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

$conn = $GLOBALS['conn'];

// ============================================
// DEFINIR CONSTANTES
// ============================================

if (!defined('APP_ENV_LOADED')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? '');
    define('DB_USER', $_ENV['DB_USER'] ?? '');
    define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');
    define('DB_NAME', $_ENV['DB_NAME'] ?? '');
    define('APP_ENV_LOADED', true);
}

?>