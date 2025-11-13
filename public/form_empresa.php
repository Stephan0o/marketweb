<?php
session_start();
require_once __DIR__ . '../../init-light.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $rubro = $_POST['rubro'];
    $anos = intval($_POST['anos']);
    $ubicacion = trim($_POST['ubicacion']);
    $equipo = $_POST['equipo'];
    $competencia = trim($_POST['competencia']);
    $diferenciador = trim($_POST['diferenciador']);
    $productos = trim($_POST['productos']);
    $descripcion = trim($_POST['descripcion']);
    $usuario_id = $_SESSION['usuario_id'];
    $sql = "INSERT INTO empresas (
        usuario_id, nombre_empresa, rubro, anos_mercado, ubicacion,
        equipo, competencia, diferenciador, productos, descripcion
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ississssss",
        $usuario_id, $nombre, $rubro, $anos, $ubicacion,
        $equipo, $competencia, $diferenciador, $productos, $descripcion
    );
    if ($stmt->execute()) {
        header("Location: panel.php?success=empresa");
        exit();
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Empresa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="container my-5 d-flex justify-content-center">
        <div class="card shadow-lg p-4" style="max-width: 800px; width:100%;">
            <h2 class="text-center mb-4">Registro de Empresa</h2>
            
            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="row g-3" id="empresaForm">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la Empresa: <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required autocomplete="organization">
                    <div class="form-text">Presiona Tab para avanzar rápido</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Rubro/Sector: <span class="text-danger">*</span></label>
                    <select name="rubro" id="rubroSelect" class="form-select" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Tecnología">Tecnología</option>
                        <option value="Alimentación">Alimentación</option>
                        <option value="Moda">Moda</option>
                        <option value="Educación">Educación</option>
                        <option value="Salud">Salud</option>
                        <option value="Turismo">Turismo</option>
                        <option value="Retail">Retail</option>
                        <option value="otros">Otros (especificar)</option>
                    </select>
                    <input type="text" name="" id="rubroInput" class="form-control mt-2" placeholder="Escribe tu rubro aquí..." style="display:none;">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Años en el mercado: <span class="text-danger">*</span></label>
                    <input type="number" name="anos" id="anos" class="form-control" min="0" max="150" required>
                    <div class="invalid-feedback">Ingresa un número válido</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Ubicación principal: <span class="text-danger">*</span></label>
                    <input type="text" name="ubicacion" id="ubicacion" class="form-control" required autocomplete="off">
                    <div id="ubicacionSugerencias" class="sugerencias-box"></div>
                    <div class="form-text">Empieza a escribir para ver sugerencias</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">¿Cuenta con equipo de marketing interno? <span class="text-danger">*</span></label>
                    <select name="equipo" id="equipo" class="form-select" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Sí">Sí</option>
                        <option value="No">No</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Principales competidores:</label>
                    <input type="text" name="competencia" id="competencia" class="form-control" placeholder="Ej: Empresa A, Empresa B">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Puntos fuertes / Diferenciadores:</label>
                    <input type="text" name="diferenciador" id="diferenciador" class="form-control" placeholder="¿Qué te hace único?">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Productos o servicios principales:</label>
                    <input type="text" name="productos" id="productos" class="form-control" placeholder="Ej: Servicio A, Producto B">
                </div>
                
                <div class="col-12">
                    <label class="form-label">Detalla una descripción de tu empresa:</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="3" maxlength="500" placeholder="Cuéntanos sobre tu empresa..."></textarea>
                    <div class="form-text text-end"><span id="charCount">0</span> / 500 caracteres</div>
                </div>
                
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5" id="submitBtn">
                        <span id="btnText">Guardar Empresa</span>
                        <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2" style="display:none;"></span>
                    </button>
                    <a href="panel.php" class="btn btn-secondary px-5 ms-2">⬅ Volver</a>
                </div>
                
                <div class="col-12 text-center mt-2">
                    <small class="text-muted">💡 Tip: Usa Tab para navegar entre campos más rápido</small>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/form-mejoras.js"></script>
</body>
</html>