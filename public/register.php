<?php
session_start();
require_once __DIR__ . '../../init-light.php';
require_once "../includes/mailer.php";

// VALIDAR DATOS Y ENVIAR CÓDIGO
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["step"] === "1") {
    $nombre = trim($_POST["nombre"]);
    $usuario = trim($_POST["usuario"]);
    $email = trim($_POST["email"]);
    $telefono = trim($_POST["telefono"]);
    $password = $_POST["password"];
    $confirmar_password = $_POST["confirmar_password"];

    // Validaciones
    if (empty($nombre) || empty($usuario) || empty($email) || empty($telefono)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } elseif ($password !== $confirmar_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "La contraseña debe contener mayúsculas, minúsculas y números.";
    } else {
        $check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "El correo ya está registrado.";
        } else {
            $codigo = generarCodigo();

            $_SESSION["registro_temp"] = [
                "nombre" => $nombre,
                "usuario" => $usuario,
                "email" => $email,
                "telefono" => $telefono,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "codigo" => $codigo,
                "timestamp" => time()
            ];

            $resultado = enviarCorreo($email, $codigo);
            
            if ($resultado['success']) {
                $success = "✅ Te enviamos un código de verificación a <strong>$email</strong>. Revisa tu bandeja de entrada y spam.";
                $mostrarCodigo = true;
            } else {
                $error = "No se pudo enviar el correo. Por favor, verifica tu correo electrónico o intenta más tarde.";
                error_log("Error SMTP: " . $resultado['error']);
            }
        }
        $check->close();
    }
}

// VERIFICAR CÓDIGO
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST["step"] === "2") {
    $codigoIngresado = trim($_POST["codigo"]);
    
    if (!isset($_SESSION["registro_temp"])) {
        $error = "La sesión de registro ha expirado. Por favor, intenta nuevamente.";
    } else {
        $temp = $_SESSION["registro_temp"];
        
        if (time() - $temp["timestamp"] > 600) {
            unset($_SESSION["registro_temp"]);
            $error = "El código ha expirado. Por favor, regístrate nuevamente.";
        } elseif ($codigoIngresado !== $temp["codigo"]) {
            $error = "❌ El código ingresado es incorrecto.";
            $mostrarCodigo = true;
        } else {
            $sql = "INSERT INTO usuarios (nombre, usuario, email, telefono, password, rol) 
                    VALUES (?, ?, ?, ?, ?, 'empresa')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", 
                $temp["nombre"], 
                $temp["usuario"], 
                $temp["email"], 
                $temp["telefono"], 
                $temp["password"]
            );

            if ($stmt->execute()) {
                unset($_SESSION["registro_temp"]);
                $_SESSION["registro_exitoso"] = true;
                header("Location: login.php?registro=exitoso");
                exit();
            } else {
                $error = "Error al registrar el usuario: " . $conn->error;
                error_log("Error BD: " . $conn->error);
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - MarketWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-lg p-4" style="max-width: 450px; width: 100%;">
        <h3 class="text-center mb-3">📝 Registro de Usuario</h3>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php if (!isset($mostrarCodigo)) : ?>
            <form method="POST" id="formRegistro" novalidate>
                <input type="hidden" name="step" value="1">

                <div class="mb-3">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" name="nombre" class="form-control" required value="<?= $_POST['nombre'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Usuario *</label>
                    <input type="text" name="usuario" class="form-control" required value="<?= $_POST['usuario'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo electrónico *</label>
                    <input type="email" name="email" id="email" class="form-control" required value="<?= $_POST['email'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Teléfono *</label>
                    <input type="text" name="telefono" class="form-control" required value="<?= $_POST['telefono'] ?? '' ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Contraseña *</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                    <div id="passwordStrength" class="mt-2"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña *</label>
                    <input type="password" name="confirmar_password" id="confirmar_password" class="form-control" required>
                    <small class="text-muted" id="passwordMatch"></small>
                </div>

                <button type="submit" class="btn btn-success w-100 mt-3">Registrarse</button>
            </form>
            <div class="mt-3 text-center">
                <small>¿Ya tienes cuenta? <a href="login.php" style="color: #4e73df; font-weight: 600;">Inicia sesión aquí</a></small>
            </div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="step" value="2">
                <div class="mb-3">
                    <label class="form-label">Código de verificación *</label>
                    <input type="text" name="codigo" class="form-control text-center" maxlength="6" pattern="[0-9]{6}" required style="font-size: 24px; letter-spacing: 10px;" placeholder="000000">
                    <small class="text-muted">El código expira en 10 minutos</small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Confirmar registro</button>
                <a href="register.php" class="btn btn-outline-secondary w-100 mt-2">Volver a intentar</a>
            </form>
        <?php endif; ?>
    </div>

    <script>
    // lógica
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("formRegistro");
        const passwordInput = document.getElementById("password");
        const confirmarInput = document.getElementById("confirmar_password");
        const emailInput = document.getElementById("email");

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthDiv = document.getElementById('passwordStrength');
                
                let strength = 0;
                let feedback = [];
                
                if (password.length >= 8) strength++;
                else feedback.push('Mínimo 8 caracteres');
                
                if (/[A-Z]/.test(password)) strength++;
                else feedback.push('Falta mayúscula');
                
                if (/[a-z]/.test(password)) strength++;
                else feedback.push('Falta minúscula');
                
                if (/[0-9]/.test(password)) strength++;
                else feedback.push('Falta número');
                
                const colors = ['danger', 'warning', 'info', 'success'];
                const labels = ['Muy débil', 'Débil', 'Media', 'Fuerte'];
                
                if (password.length > 0) {
                    strengthDiv.innerHTML = `
                        <div class="alert alert-${colors[strength-1]} py-2 mb-0">
                            <small><strong>Fortaleza:</strong> ${labels[strength-1]}</small>
                            ${feedback.length > 0 ? '<br><small>' + feedback.join(', ') + '</small>' : ''}
                        </div>
                    `;
                } else {
                    strengthDiv.innerHTML = '';
                }
            });
        }

        if (confirmarInput) {
            confirmarInput.addEventListener('input', function() {
                const nueva = passwordInput.value;
                const confirmar = this.value;
                const matchDiv = document.getElementById('passwordMatch');
                
                if (confirmar.length > 0) {
                    if (nueva === confirmar) {
                        matchDiv.innerHTML = '<span class="text-success">✓ Las contraseñas coinciden</span>';
                    } else {
                        matchDiv.innerHTML = '<span class="text-danger">✗ Las contraseñas no coinciden</span>';
                    }
                } else {
                    matchDiv.innerHTML = '';
                }
            });
        }

        if (form) {
            form.addEventListener("submit", (e) => {
                const password = passwordInput.value;
                const confirm = confirmarInput.value;
                const email = emailInput.value;
                
                if (!email.includes("@") || !email.includes(".")) {
                    e.preventDefault();
                    alert("Por favor, introduce un correo electrónico válido.");
                    return;
                }
                
                if (password !== confirm) {
                    e.preventDefault();
                    alert("Las contraseñas no coinciden.");
                    return;
                }
                
                if (password.length < 8) {
                    e.preventDefault();
                    alert("La contraseña debe tener al menos 8 caracteres.");
                    return;
                }
                
                if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
                    e.preventDefault();
                    alert("La contraseña no cumple con los requisitos de seguridad.");
                    return;
                }
            });
        }
    });
    </script>
</body>
</html>