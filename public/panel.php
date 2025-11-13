<?php
session_start();
require_once __DIR__ . '../../init-light.php';

if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION["usuario_id"];
$nombre = $_SESSION["usuario_nombre"];
$rol = $_SESSION["usuario_rol"];

// Verificar si ya existe una empresa registrada por el user
$sql = "SELECT id FROM empresas WHERE usuario_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$tieneEmpresa = $result->num_rows > 0;
$empresa_id = $tieneEmpresa ? $result->fetch_assoc()['id'] : null;

// Verificar si hay campañas registradas
$tieneCampanas = false;
if ($tieneEmpresa) {
    $sql_campanas = "SELECT id FROM campañas WHERE empresa_id = ? LIMIT 1";
    $stmt_campanas = $conn->prepare($sql_campanas);
    $stmt_campanas->bind_param("i", $empresa_id);
    $stmt_campanas->execute();
    $result_campanas = $stmt_campanas->get_result();
    $tieneCampanas = $result_campanas->num_rows > 0;
    $stmt_campanas->close();
}

// Revisar si hay notificación
$success = isset($_GET['success']) ? $_GET['success'] : '';
$mensaje = '';
if ($success === 'empresa') {
    $mensaje = "✅ Empresa registrada correctamente";
} elseif ($success === 'campania') {
    $mensaje = "✅ Campaña registrada correctamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Usuario - MarketWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/panel2.css" rel="stylesheet">
</head>
<body>

<!-- barra fijo -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <span class="logo-icon">🚀</span>
            <h3>MarketWeb</h3>
        </div>
        <div class="user-info">
            <p class="user-name"><?= htmlspecialchars($nombre) ?></p>
            <p class="user-role"><?= htmlspecialchars($rol) ?></p>
        </div>
    </div>
    <ul class="sidebar-nav">
        <li><a href="perfil.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span class="nav-text">Perfil</span>
        </a></li>
        <li><a href="#" class="nav-item sidebar-link" data-action="editar-empresa">
            <span class="nav-icon">🏢</span>
            <span class="nav-text">Edita tu Empresa</span>
        </a></li>
        <li><a href="#" class="nav-item sidebar-link" data-action="campanas">
            <span class="nav-icon">🎯</span>
            <span class="nav-text">Campañas</span>
        </a></li>
        <li><a href="#" class="nav-item sidebar-link" data-action="estrategias">
            <span class="nav-icon">⚡</span>
            <span class="nav-text">Estrategias</span>
        </a></li>
        <li><a href="historial_conversaciones.php" class="nav-item">
            <span class="nav-icon">📜</span>
            <span class="nav-text">Chats IA</span>
        </a></li>
        <li><a href="../backend/updates/logout.php" class="nav-item nav-logout">
            <span class="nav-icon">🚪</span>
            <span class="nav-text">Salir</span>
        </a></li>
    </ul>
</div>

<!-- Contenido principal -->
<div class="main-content">
    <div class="container-fluid py-5">
        <div class="welcome-section mb-5">
            <div class="welcome-content">
                <h1 class="welcome-title">Bienvenido, <span class="user-highlight"><?php echo htmlspecialchars($nombre); ?></span> 👋</h1>
                <p class="welcome-subtitle">Gestiona tu estrategia de marketing de forma inteligente</p>
            </div>
            <div class="status-badge">
                <span class="status-indicator"></span>
                <span class="status-text"><?php echo htmlspecialchars($rol); ?></span>
            </div>
        </div>

        <!-- Estadísticas del user -->
        <div class="quick-stats mb-5">
            <div class="stat-card stat-primary">
                <div class="stat-icon">🏢</div>
                <div class="stat-content">
                    <h6>Empresa</h6>
                    <p class="stat-value"><?php echo $tieneEmpresa ? '✓ Registrada' : '✗ No registrada'; ?></p>
                </div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">🎯</div>
                <div class="stat-content">
                    <h6>Campañas</h6>
                    <p class="stat-value"><?php echo $tieneCampanas ? '✓ Activas' : '✗ Sin campañas'; ?></p>
                </div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon">⚡</div>
                <div class="stat-content">
                    <h6>Estado</h6>
                    <p class="stat-value"><?php echo $tieneEmpresa && $tieneCampanas ? 'Listo' : 'En configuración'; ?></p>
                </div>
            </div>
        </div>

        <!-- Módulos -->
        <div class="section-title mb-4">
            <h2>Opciones disponibles</h2>
            <p class="text-muted">Selecciona una opción para continuar</p>
        </div>

        <div class="options-grid">
            <?php if (!$tieneEmpresa): ?>
                <div class="option-card-wrapper">
                    <a href="form_empresa.php" class="option-card text-decoration-none text-white option-primary">
                        <div class="card-body">
                            <div class="card-icon">📄</div>
                            <h5 class="card-title">Registrar Empresa</h5>
                            <p class="card-description">Completa los datos de tu empresa</p>
                            <div class="card-footer">
                                <span class="badge bg-primary">Requerido</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php else: ?>
                <div class="option-card-wrapper">
                    <a href="form_campania.php" class="option-card text-decoration-none text-white option-success">
                        <div class="card-body">
                            <div class="card-icon">🎯</div>
                            <h5 class="card-title">Registrar Nueva Campaña</h5>
                            <p class="card-description">Enfoca tu estrategia en un objetivo específico</p>
                            <div class="card-footer">
                                <span class="badge bg-success">Disponible</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>

            <div class="option-card-wrapper">
                <a href="#" class="option-card text-decoration-none text-white card-link option-info" data-action="estrategias">
                    <div class="card-body">
                        <div class="card-icon">⚡</div>
                        <h5 class="card-title">Generar Estrategias</h5>
                        <p class="card-description">Crea y gestiona tus estrategias de marketing</p>
                        <div class="card-footer">
                            <span class="badge <?php echo $tieneCampanas ? 'bg-success' : 'bg-warning'; ?>">
                                <?php echo $tieneCampanas ? 'Disponible' : 'Requiere campaña'; ?>
                            </span>
                        </div>
                    </div>
                </a>
            </div>

            <div class="option-card-wrapper">
                <a href="../backend/updates/logout.php" class="option-card text-decoration-none text-white option-danger">
                    <div class="card-body">
                        <div class="card-icon">🚪</div>
                        <h5 class="card-title">Cerrar Sesión</h5>
                        <p class="card-description">Salir de tu cuenta</p>
                        <div class="card-footer">
                            <span class="badge bg-secondary">Seguro</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Toast de notificación -->
<?php if ($mensaje): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="successToast" class="toast align-items-center text-bg-success border-0 shadow-lg" 
         role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $mensaje; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" 
                    data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal de acvertencias -->
<div class="modal fade" id="warningModal" tabindex="-1" aria-labelledby="warningLabel">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-warning border-0">
                <h5 class="modal-title fw-bold text-dark" id="warningLabel">
                    <span class="warning-icon">⚠️</span> Acción no disponible
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-4" id="warningMessage">
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="actionButton" class="btn btn-primary fw-bold">
                    <span>→</span> Ir ahora
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const toastEl = document.getElementById("successToast");
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 }); 
        toast.show();
    }

    const tieneEmpresa = <?php echo json_encode($tieneEmpresa); ?>;
    const tieneCampanas = <?php echo json_encode($tieneCampanas); ?>;
    
    const warningModalElement = document.getElementById('warningModal');
    
    warningModalElement.removeAttribute('aria-hidden');
    warningModalElement.setAttribute('inert', '');
    
    // Remover aria-hidden cuando se abre
    warningModalElement.addEventListener('show.bs.modal', function() {
        this.removeAttribute('aria-hidden');
        this.removeAttribute('inert');
    });
    
    warningModalElement.addEventListener('hide.bs.modal', function() {
        this.setAttribute('inert', '');
    });
    
    const warningModal = new bootstrap.Modal(warningModalElement);

    document.querySelectorAll('.sidebar-link, .card-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.getAttribute('data-action');
            handleAction(action);
        });
    });

    function handleAction(action) {
        const warningMsg = document.getElementById('warningMessage');
        const actionBtn = document.getElementById('actionButton');

        if (action === 'editar-empresa') {
            if (!tieneEmpresa) {
                warningMsg.innerHTML = '<p class="alert alert-info">Aún no has registrado tu empresa. Debes completar el registro de tu empresa antes de poder editarla.</p>';
                actionBtn.href = 'form_empresa.php';
                actionBtn.innerHTML = '→ Registrar Empresa';
                warningModal.show();
            } else {
                window.location.href = 'editar_empresa.php';
            }
        } else if (action === 'campanas') {
            if (!tieneEmpresa) {
                warningMsg.innerHTML = '<p class="alert alert-info">Debes registrar tu empresa primero antes de crear campañas.</p>';
                actionBtn.href = 'form_empresa.php';
                actionBtn.innerHTML = '→ Registrar Empresa';
                warningModal.show();
            } else {
                window.location.href = 'form_campania.php';
            }
        } else if (action === 'estrategias') {
            if (!tieneEmpresa) {
                warningMsg.innerHTML = '<p class="alert alert-info">Debes registrar tu empresa primero antes de crear estrategias.</p>';
                actionBtn.href = 'form_empresa.php';
                actionBtn.innerHTML = '→ Registrar Empresa';
                warningModal.show();
            } else if (!tieneCampanas) {
                warningMsg.innerHTML = '<p class="alert alert-info">Aún no has registrado ninguna campaña. Debes crear una campaña antes de generar estrategias.</p>';
                actionBtn.href = 'form_campania.php';
                actionBtn.innerHTML = '→ Crear Campaña';
                warningModal.show();
            } else {
                window.location.href = 'estrategias.php';
            }
        }
    }
});
</script>

<?php include '../includes/chat_widget.php'; ?>

</body>
</html>