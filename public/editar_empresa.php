<?php
session_start();
require_once __DIR__ . '../../init-light.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener empresa asociada al usuario
$sql = "SELECT * FROM empresas WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$empresa = $result->fetch_assoc();

if (!$empresa) {
    // Si no existe empresa, redirigir al registro
    header("Location: registrar_empresa.php");
    exit();
}

$message = "";
$messageType = "";

// Actualizar datos de empresa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = trim($_POST['nombre_empresa'] ?? $empresa['nombre_empresa']);
    $rubro          = $_POST['rubro'] ?? $empresa['rubro'];
    $anos_mercado   = $_POST['anos_mercado'] ?? $empresa['anos_mercado'];
    $ubicacion      = trim($_POST['ubicacion'] ?? $empresa['ubicacion']);
    $equipo         = $_POST['equipo'] ?? $empresa['equipo'];
    $competencia    = trim($_POST['competencia'] ?? $empresa['competencia']);
    $diferenciador  = trim($_POST['diferenciador'] ?? $empresa['diferenciador']);
    $productos      = trim($_POST['productos'] ?? $empresa['productos']);
    $descripcion    = trim($_POST['descripcion'] ?? $empresa['descripcion']);

    $sql_update = "UPDATE empresas 
                   SET nombre_empresa=?, rubro=?, anos_mercado=?, ubicacion=?, equipo=?, competencia=?, diferenciador=?, productos=?, descripcion=? 
                   WHERE id=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param(
        "ssissssssi",
        $nombre_empresa,
        $rubro,
        $anos_mercado,
        $ubicacion,
        $equipo,
        $competencia,
        $diferenciador,
        $productos,
        $descripcion,
        $empresa['id']
    );

    if ($stmt_update->execute()) {
        $message = "✅ Datos de la empresa actualizados correctamente.";
        $messageType = "success";

        // Refrescar empresa desde la BD
        $sql_refresh = "SELECT * FROM empresas WHERE id=?";
        $stmt_refresh = $conn->prepare($sql_refresh);
        $stmt_refresh->bind_param("i", $empresa['id']);
        $stmt_refresh->execute();
        $empresa = $stmt_refresh->get_result()->fetch_assoc();
    } else {
        $message = "❌ Error al actualizar la empresa. Intente nuevamente.";
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - <?= htmlspecialchars($empresa['nombre_empresa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/editar_empresa.css">
    <link rel="stylesheet" href="assets/css/custom.css">

</head>
<body class="p-4">

<div class="container">
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h1 class="mb-2">🏢 Editar Información de Empresa</h1>
                    <p class="text-white mb-0">Actualiza los datos de tu empresa en cualquier momento</p>
                </div>
                <a href="panel.php" class="btn btn-light">⬅ Volver al Panel</a>
            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Card de información actual -->
    <div class="info-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5>📊 Empresa Actual</h5>
                <p><strong><?= htmlspecialchars($empresa['nombre_empresa']) ?></strong></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <span class="empresa-badge">
                    📍 <?= htmlspecialchars($empresa['ubicacion']) ?>
                </span>
                <span class="empresa-badge">
                    🏷️ <?= htmlspecialchars($empresa['rubro']) ?>
                </span>
                <span class="empresa-badge">
                    📅 <?= htmlspecialchars($empresa['anos_mercado']) ?> años
                </span>
            </div>
        </div>
    </div>

    <form method="POST" action="" id="formEmpresa">
        <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
        <div class="section-card">
            <div class="section-header">
                📋 Información Básica
            </div>
            <div class="section-body">
                <!-- Nombre Empresa -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">🏢</span>
                        Nombre de la Empresa
                    </label>
                    <input type="text" 
                           name="nombre_empresa" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['nombre_empresa']) ?>" 
                           readonly>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Rubro -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">🏷️</span>
                        Rubro/Sector
                    </label>
                    <select name="rubro" class="form-select field-input" disabled>
                        <option value="">-- Selecciona --</option>
                        <option value="Tecnología" <?= $empresa['rubro']=="Tecnología" ? "selected" : "" ?>>Tecnología</option>
                        <option value="Alimentación" <?= $empresa['rubro']=="Alimentación" ? "selected" : "" ?>>Alimentación</option>
                        <option value="Moda" <?= $empresa['rubro']=="Moda" ? "selected" : "" ?>>Moda</option>
                        <option value="Educación" <?= $empresa['rubro']=="Educación" ? "selected" : "" ?>>Educación</option>
                        <option value="Salud" <?= $empresa['rubro']=="Salud" ? "selected" : "" ?>>Salud</option>
                        <option value="Turismo" <?= $empresa['rubro']=="Turismo" ? "selected" : "" ?>>Turismo</option>
                        <option value="Retail" <?= $empresa['rubro']=="Retail" ? "selected" : "" ?>>Retail</option>
                        <option value="Otros" <?= $empresa['rubro']=="Otros" ? "selected" : "" ?>>Otros</option>
                    </select>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Años en mercado -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">📅</span>
                        Años en el mercado
                    </label>
                    <input type="number" 
                           name="anos_mercado" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['anos_mercado']) ?>" 
                           readonly
                           min="0">
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Ubicación -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">📍</span>
                        Ubicación principal
                    </label>
                    <input type="text" 
                           name="ubicacion" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['ubicacion']) ?>" 
                           readonly
                           placeholder="Ej: Lima, Perú">
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Equipo marketing -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">👥</span>
                        ¿Cuenta con equipo de marketing interno?
                    </label>
                    <select name="equipo" class="form-select field-input" disabled>
                        <option value="">-- Selecciona --</option>
                        <option value="Sí" <?= $empresa['equipo']=="Sí" ? "selected" : "" ?>>Sí</option>
                        <option value="No" <?= $empresa['equipo']=="No" ? "selected" : "" ?>>No</option>
                    </select>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: ANÁLISIS COMPETITIVO -->
        <div class="section-card">
            <div class="section-header">
                🎯 Análisis Competitivo y Diferenciación
            </div>
            <div class="section-body">
                <!-- Competencia -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">⚔️</span>
                        Principales competidores
                    </label>
                    <input type="text" 
                           name="competencia" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['competencia']) ?>" 
                           readonly
                           placeholder="Ej: Empresa A, Empresa B, Empresa C">
                    <small class="text-muted">Menciona tus principales competidores separados por comas</small>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Diferenciadores -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">⭐</span>
                        Puntos fuertes / Diferenciadores
                    </label>
                    <input type="text" 
                           name="diferenciador" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['diferenciador']) ?>" 
                           readonly
                           placeholder="Ej: Calidad premium, Atención personalizada">
                    <small class="text-muted">¿Qué te hace único frente a la competencia?</small>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 3: PRODUCTOS Y DESCRIPCIÓN -->
        <div class="section-card">
            <div class="section-header">
                📦 Productos y Descripción
            </div>
            <div class="section-body">
                <!-- Productos -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">📦</span>
                        Productos o servicios principales
                    </label>
                    <input type="text" 
                           name="productos" 
                           class="form-control field-input" 
                           value="<?= htmlspecialchars($empresa['productos']) ?>" 
                           readonly
                           placeholder="Ej: Software SaaS, Consultoría, Desarrollo web">
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>

                <!-- Descripción -->
                <div class="field-group">
                    <label class="field-label">
                        <span class="icon-field">📝</span>
                        Descripción de la empresa
                    </label>
                    <textarea name="descripcion" 
                              class="form-control field-input" 
                              rows="4" 
                              readonly
                              placeholder="Describe brevemente tu empresa, misión y visión..."><?= htmlspecialchars($empresa['descripcion']) ?></textarea>
                    <small class="text-muted">Una descripción clara ayuda a generar mejores estrategias</small>
                    <button type="button" class="btn btn-warning edit-btn">✏️ Editar</button>
                </div>
            </div>
        </div>

        <!-- Sección de guardado -->
        <div class="save-section">
            <h5 class="mb-3">💾 Guardar Cambios</h5>
            <p class="text-muted mb-4">Asegúrate de haber editado los campos que deseas actualizar antes de guardar</p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    💾 Guardar Todos los Cambios
                </button>
                <a href="panel.php" class="btn btn-secondary btn-lg px-5">
                    ❌ Cancelar
                </a>
            </div>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar botones de editar
    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const fieldGroup = this.closest('.field-group');
            const input = fieldGroup.querySelector("input, textarea, select");
            
            if (input) {
                // Habilitar edición
                if (input.tagName === "SELECT") {
                    input.disabled = false;
                } else {
                    input.removeAttribute("readonly");
                }
                
                input.focus();
                
                // Cambiar estilo del botón
                this.classList.remove("btn-warning");
                this.classList.add("btn-success");
                this.innerHTML = "✓ Listo";
                
                // Agregar clase de edición al field-group
                fieldGroup.classList.add("editing");
                
                // Opcional: volver a cambiar el botón si se pierde el foco
                input.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (this.classList.contains("btn-success")) {
                            this.classList.remove("btn-success");
                            this.classList.add("btn-warning");
                            this.innerHTML = "✏️ Editar";
                        }
                    }, 200);
                }, { once: true });
            }
        });
    });

    // Validación antes de enviar
    document.getElementById('formEmpresa').addEventListener('submit', function(e) {
        const nombre = document.querySelector('[name="nombre_empresa"]').value.trim();
        const rubro = document.querySelector('[name="rubro"]').value;
        
        if (!nombre) {
            e.preventDefault();
            alert('El nombre de la empresa es obligatorio');
            return;
        }
        
        if (!rubro) {
            e.preventDefault();
            alert('Debe seleccionar un rubro');
            return;
        }
    });
});
</script>
</body>
</html>