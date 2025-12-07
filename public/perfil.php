<?php
session_start();
require_once __DIR__ . '../../init-light.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$message = "";
$messageType = "";

$sql = "SELECT id, nombre, usuario, email, telefono, zona_horaria, creado_en, rol 
        FROM usuarios 
        WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

if (!$usuario) {
    header("Location: login.php");
    exit();
}

// estadísticas de uso
$sqlEstadisticas = "SELECT 
    (SELECT COUNT(*) FROM empresas WHERE usuario_id = ?) as total_empresas,
    (SELECT COUNT(*) FROM campañas c 
     JOIN empresas e ON c.empresa_id = e.id 
     WHERE e.usuario_id = ?) as total_campanias,
    (SELECT COUNT(*) FROM estrategias es 
     JOIN campañas c ON es.campania_id = c.id 
     JOIN empresas e ON c.empresa_id = e.id 
     WHERE e.usuario_id = ?) as total_estrategias";
$stmtStats = $conn->prepare($sqlEstadisticas);
$stmtStats->bind_param("iii", $usuario_id, $usuario_id, $usuario_id);
$stmtStats->execute();
$estadisticas = $stmtStats->get_result()->fetch_assoc();

// lógica de actualzación
require_once "../backend/updates/actualizar_info_perfil.php";
require_once "../backend/updates/cambiar_password.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= htmlspecialchars($usuario['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/perfil.css">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="mb-2">👤 Mi Perfil</h1>
                        <p class="text-muted mb-0">Gestiona tu información personal y preferencias de cuenta</p>
                    </div>
                    <a href="panel.php" class="btn btn-secondary">⬅ Volver al Panel</a>
                </div>
            </div>
        </div>

        <!-- Mensajes de retroalimentación -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="section-card">
                    <div class="section-header">
                        📋 Información Personal
                    </div>
                    <div class="section-body">
                        <div class="mb-4">
                            <h6 class="text-muted mb-3">Datos de la cuenta</h6>
                            <div class="info-row">
                                <span class="info-label">Nombre completo:</span>
                                <span class="info-value"><?= htmlspecialchars($usuario['nombre']) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Rol:</span>
                                <span class="info-value">
                                    <span class="badge-role"><?= ucfirst($usuario['rol']) ?></span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Miembro desde:</span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($usuario['creado_en'])) ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Zona horaria:</span>
                                <span class="info-value"><?= htmlspecialchars($usuario['zona_horaria']) ?></span>
                            </div>
                        </div>

                        <!-- Datos editables -->
                        <form method="POST" action="">
                            <h6 class="text-muted mb-3">Datos editables</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Nombre de usuario <span class="text-danger">*</span></label>
                                <input type="text" name="usuario" class="form-control" 
                                       value="<?= htmlspecialchars($usuario['usuario']) ?>" required>
                                <small class="text-muted">Este es tu identificador único en el sistema.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Correo electrónico <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($usuario['email']) ?>" required>
                                <small class="text-muted">Usaremos este correo para notificaciones importantes.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" 
                                       value="<?= htmlspecialchars($usuario['telefono']) ?>" 
                                       placeholder="Ej: +51 999 999 999">
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" name="actualizar_info" class="btn btn-primary px-4">
                                    💾 Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cambiar Contraseña -->
                <div class="section-card">
                    <div class="section-header">
                        🔒 Seguridad - Cambiar Contraseña
                    </div>
                    <div class="section-body">
                        <form method="POST" action="" id="formPassword">
                            <div class="mb-3">
                                <label class="form-label">Contraseña actual <span class="text-danger">*</span></label>
                                <input type="password" name="password_actual" class="form-control" 
                                       required placeholder="Ingresa tu contraseña actual">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nueva contraseña <span class="text-danger">*</span></label>
                                <input type="password" name="password_nueva" class="form-control" 
                                       id="password_nueva" required 
                                       placeholder="Mínimo 8 caracteres">
                                <div class="password-strength mt-2" id="passwordStrength"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirmar nueva contraseña <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmar" class="form-control" 
                                       id="password_confirmar" required 
                                       placeholder="Repite la nueva contraseña">
                                <small class="text-muted" id="passwordMatch"></small>
                            </div>

                            <div class="password-requirements">
                                <strong>Requisitos de seguridad:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Mínimo 8 caracteres</li>
                                    <li>Al menos una letra mayúscula (A-Z)</li>
                                    <li>Al menos una letra minúscula (a-z)</li>
                                    <li>Al menos un número (0-9)</li>
                                </ul>
                            </div>

                            <div class="text-end mt-4">
                                <button type="submit" name="cambiar_password" class="btn btn-warning px-4">
                                    🔐 Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Estadística -->
            <div class="col-lg-4">
                <div class="section-card">
                    <div class="section-header">
                        📊 Estadísticas de Uso
                    </div>
                    <div class="section-body">
                        <div class="stat-card mb-3">
                            <div class="stat-number"><?= $estadisticas['total_empresas'] ?></div>
                            <div class="stat-label">Empresas Registradas</div>
                        </div>

                        <div class="stat-card mb-3" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <div class="stat-number"><?= $estadisticas['total_campanias'] ?></div>
                            <div class="stat-label">Campañas Creadas</div>
                        </div>

                        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <div class="stat-number"><?= $estadisticas['total_estrategias'] ?></div>
                            <div class="stat-label">Estrategias Generadas</div>
                        </div>

                        <div class="mt-4 p-3" style="background: #f8f9fa; border-radius: 8px;">
                            <h6 class="mb-3">⚡ Actividad Reciente</h6>
                            <small class="text-muted">
                                <?php
                                $sqlUltima = "SELECT c.creado_en 
                                              FROM campañas c 
                                              JOIN empresas e ON c.empresa_id = e.id 
                                              WHERE e.usuario_id = ? 
                                              ORDER BY c.creado_en DESC LIMIT 1";
                                $stmtUltima = $conn->prepare($sqlUltima);
                                $stmtUltima->bind_param("i", $usuario_id);
                                $stmtUltima->execute();
                                $ultima = $stmtUltima->get_result()->fetch_assoc();
                                
                                if ($ultima) {
                                    echo "Última campaña creada: <br><strong>" . date('d/m/Y H:i', strtotime($ultima['creado_en'])) . "</strong>";
                                } else {
                                    echo "Aún no has creado campañas.";
                                }
                                ?>
                            </small>
                        </div>

                        <div class="mt-3 text-center">
                            <a href="estrategias.php" class="btn btn-sm btn-outline-primary w-100">
                                Ver Todas las Campañas →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // lógica de contraseña
        document.getElementById('password_nueva').addEventListener('input', function() {
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

        // Validar que las contraseñas coincidan
        document.getElementById('password_confirmar').addEventListener('input', function() {
            const nueva = document.getElementById('password_nueva').value;
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
    </script>
</body>
</html>