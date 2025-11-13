<?php
session_start();
require_once __DIR__ . '/../../init-light.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "Acceso no autorizado.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['id']) && isset($_POST['estado'])) {
        $id = intval($_POST['id']);
        $estado = trim($_POST['estado']);
        $estadosPermitidos = ["Pendiente", "En curso", "Finalizada"];
        if (!in_array($estado, $estadosPermitidos)) {
            echo "Estado inválido.";
            exit();
        }

        $sql = "UPDATE detalle_estrategias SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $estado, $id);

        if ($stmt->execute()) {
            echo "Estado actualizado correctamente.";
        } else {
            echo "Error al actualizar estado.";
        }
    } else {
        echo "Parámetros incompletos.";
    }
} else {
    echo "Método no permitido.";
}
