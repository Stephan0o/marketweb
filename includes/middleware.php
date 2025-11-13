<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar rol requerido
function verificarRol($rolPermitido) {
    if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] !== $rolPermitido) {
        // Si no tiene el rol correcto → redirigir
        header("Location: no_autorizado.php");
        exit();
    }
}

