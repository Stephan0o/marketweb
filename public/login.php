<?php
session_start();
require_once __DIR__ . '../../init-light.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT id, nombre, password, rol FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        if (password_verify($password, $usuario["password"])) {
            $_SESSION["usuario_id"] = $usuario["id"];
            $_SESSION["usuario_nombre"] = $usuario["nombre"];
            $_SESSION["usuario_rol"] = $usuario["rol"];

            if ($usuario["rol"] === "admin") {
                header("Location: panel_admin.php");
            } else {
                header("Location: panel.php");
            }
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "No existe una cuenta con ese correo.";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="card">
        <h3>🔐 Iniciar Sesión</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <!-- Correo -->
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-control" 
                        placeholder="correo@gmail.com" 
                        required
                        autocomplete="email"
                    >
                </div>
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input 
                        type="password" 
                        id="password"
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••" 
                        required
                        autocomplete="current-password"
                    >
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                Iniciar sesión
            </button>
        </form>
        <div class="mt-3">
            <small>¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>