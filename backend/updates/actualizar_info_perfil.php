<?php
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['actualizar_info'])) {
    $nuevo_usuario = trim($_POST['usuario']);
    $nuevo_email = trim($_POST['email']);
    $nuevo_telefono = trim($_POST['telefono']);

    // Validar que el usuario no esté en uso por otro
    $sqlCheck = "SELECT id FROM usuarios WHERE usuario = ? AND id != ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("si", $nuevo_usuario, $usuario_id);
    $stmtCheck->execute();
    
    if ($stmtCheck->get_result()->num_rows > 0) {
        $message = "El nombre de usuario ya está en uso.";
        $messageType = "danger";
    } else {
        // Actualizar información
        $sqlUpdate = "UPDATE usuarios SET usuario = ?, email = ?, telefono = ? WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("sssi", $nuevo_usuario, $nuevo_email, $nuevo_telefono, $usuario_id);
        
        if ($stmtUpdate->execute()) {
            $message = "✅ Información actualizada correctamente.";
            $messageType = "success";
            $usuario['usuario'] = $nuevo_usuario;
            $usuario['email'] = $nuevo_email;
            $usuario['telefono'] = $nuevo_telefono;
        } else {
            $message = "❌ Error al actualizar la información.";
            $messageType = "danger";
        }
    }
}
?>