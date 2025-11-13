<?php

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];

    // Verificar contraseña actual
    $sqlPass = "SELECT password FROM usuarios WHERE id = ?";
    $stmtPass = $conn->prepare($sqlPass);
    $stmtPass->bind_param("i", $usuario_id);
    $stmtPass->execute();
    $resultPass = $stmtPass->get_result()->fetch_assoc();

    // Contraseña actual
    if (!password_verify($password_actual, $resultPass['password'])) {
        $message = "❌ La contraseña actual es incorrecta.";
        $messageType = "danger";
    }
    // Contraseñas coinciden
    elseif ($password_nueva !== $password_confirmar) {
        $message = "❌ Las contraseñas nuevas no coinciden.";
        $messageType = "danger";
    }
    // Longitud mínima
    elseif (strlen($password_nueva) < 8) {
        $message = "❌ La contraseña debe tener al menos 8 caracteres.";
        $messageType = "danger";
    }
    // Requisitos de complejidad
    elseif (!preg_match('/[A-Z]/', $password_nueva) || !preg_match('/[a-z]/', $password_nueva) || !preg_match('/[0-9]/', $password_nueva)) {
        $message = "❌ La contraseña debe contener mayúsculas, minúsculas y números.";
        $messageType = "danger";
    }
    else {
        $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $sqlUpdatePass = "UPDATE usuarios SET password = ? WHERE id = ?";
        $stmtUpdatePass = $conn->prepare($sqlUpdatePass);
        $stmtUpdatePass->bind_param("si", $password_hash, $usuario_id);
        
        if ($stmtUpdatePass->execute()) {
            $message = "✅ Contraseña actualizada correctamente.";
            $messageType = "success";
        } else {
            $message = "❌ Error al actualizar la contraseña.";
            $messageType = "danger";
        }
    }
}
?>