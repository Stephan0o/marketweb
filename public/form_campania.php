<?php
session_start();
require_once __DIR__ . '../../init-light.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Buscar la empresa asociada al usuario
$sql = "SELECT id, nombre_empresa, rubro, anos_mercado, ubicacion 
        FROM empresas 
        WHERE usuario_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$empresa = $result->fetch_assoc();

if (!$empresa) {
    header("Location: panel.php");
    exit();
}

$empresa_id = $empresa['id'];

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST['nombre']);
    $objetivo = trim($_POST['objetivo']);
    $publico = trim($_POST['publico']);
    $presupuesto = trim($_POST['presupuesto']);
    
    $canales = isset($_POST['canales']) ? implode(', ', $_POST['canales']) : '';
    
    $redes = isset($_POST['redes']) ? implode(', ', $_POST['redes']) : '';
    
    $inicio = $_POST['inicio'];
    $fin = $_POST['fin'];
    $estrategia = trim($_POST['estrategia']);
    $comentarios = trim($_POST['comentarios']);

    $sql = "INSERT INTO campañas (
        empresa_id, nombre_campaña, objetivo, publico, presupuesto_marketing,
        canales, redes, duracion_inicio, duracion_fin, estrategia, comentarios, creado_en
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "issssssssss",
        $empresa_id, $nombre, $objetivo, $publico, $presupuesto,
        $canales, $redes, $inicio, $fin, $estrategia, $comentarios
    );

    if ($stmt->execute()) {
        header("Location: panel.php?success=campania");
        exit();
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <meta charset="UTF-8">
    <title>Registrar Campaña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div class="container my-5 d-flex justify-content-center">
        <div class="card shadow-lg p-4" style="max-width: 800px; width:100%;">
            <h2 class="text-center mb-4">Registro de Campaña</h2>

            <div class="progress mb-4" style="height: 6px;">
                <div id="progressBar" class="progress-bar bg-success" role="progressbar" style="width: 0%"></div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-danger"><?= $message ?></div>
            <?php endif; ?>

            <!-- Información de la empresa -->
            <div class="accordion mb-4" id="empresaAccordion">
                <div class="accordion-item">
                    <h3 class="accordion-header" id="headingEmpresa">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmpresa" aria-expanded="false" aria-controls="collapseEmpresa">
                            📋 Información de tu Empresa
                        </button>
                    </h3>
                    <div id="collapseEmpresa" class="accordion-collapse collapse" aria-labelledby="headingEmpresa" data-bs-parent="#empresaAccordion">
                        <div class="accordion-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre:</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($empresa['nombre_empresa']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rubro:</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($empresa['rubro']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Años en el mercado:</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($empresa['anos_mercado']) ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Ubicación:</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($empresa['ubicacion']) ?>" readonly>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">
                                💡 Estos datos pertenecen a la empresa registrada y no pueden editarse desde aquí.
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de campaña -->
            <form method="POST" action="" class="row g-3" id="campaniaForm">
                <div class="col-md-6">
                    <label class="form-label">Nombre de la campaña: <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="nombreCampania" class="form-control" required>
                    <div class="form-text">Dale un nombre memorable a tu campaña</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Objetivo: <span class="text-danger">*</span></label>
                    <select name="objetivo" id="objetivoSelect" class="form-select" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Aumentar ventas">Aumentar ventas</option>
                        <option value="Mejorar reconocimiento de marca">Mejorar reconocimiento de marca</option>
                        <option value="Fidelizar clientes">Fidelizar clientes</option>
                        <option value="Lanzar nuevo producto">Lanzar nuevo producto</option>
                        <option value="otros">Otro (especificar)</option>
                    </select>
                    <input type="text" name="" id="objetivoInput" class="form-control mt-2" placeholder="Describe tu objetivo..." style="display:none;">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Público objetivo: <span class="text-danger">*</span></label>
                    <select name="publico" id="publicoSelect" class="form-select" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Jóvenes (18-25 años)">Jóvenes (18-25 años)</option>
                        <option value="Adultos jóvenes (26-35 años)">Adultos jóvenes (26-35 años)</option>
                        <option value="Adultos (36-50 años)">Adultos (36-50 años)</option>
                        <option value="Adultos mayores (50+ años)">Adultos mayores (50+ años)</option>
                        <option value="Empresas B2B">Empresas B2B</option>
                        <option value="Familias">Familias</option>
                        <option value="otros">Otro (especificar)</option>
                    </select>
                    <input type="text" name="" id="publicoInput" class="form-control mt-2" placeholder="Describe tu público objetivo..." style="display:none;">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Presupuesto (PEN): <span class="text-danger">*</span></label>
                    <select name="presupuesto" id="presupuestoSelect" class="form-select" required>
                        <option value="">-- Selecciona --</option>
                        <option value="S/ 200 - S/ 500">S/ 200 - S/ 500</option>
                        <option value="S/ 500 - S/ 1,000">S/ 500 - S/ 1,000</option>
                        <option value="S/ 1,000 - S/ 2,000">S/ 1,000 - S/ 2,000</option>
                        <option value="S/ 2,000 - S/ 5,000">S/ 2,000 - S/ 5,000</option>
                        <option value="S/ 5,000 - S/ 10,000">S/ 5,000 - S/ 10,000</option>
                        <option value="S/ 10,000+">S/ 10,000+</option>
                        <option value="otros">Otro monto (especificar)</option>
                    </select>
                    <input type="text" name="" id="presupuestoInput" class="form-control mt-2" placeholder="Ej: S/ 3,500" style="display:none;">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Canales de difusión: <span class="text-danger">*</span></label>
                    <div id="canalesContainer" class="multi-select-container">
                        <div class="selected-items" id="canalesSelected">
                            <span class="placeholder-text">Selecciona uno o varios canales...</span>
                        </div>
                        <div class="dropdown-options" id="canalesOptions">
                            <label><input type="checkbox" name="canales[]" value="TV"> TV</label>
                            <label><input type="checkbox" name="canales[]" value="Radio"> Radio</label>
                            <label><input type="checkbox" name="canales[]" value="Email Marketing"> Email Marketing</label>
                            <label><input type="checkbox" name="canales[]" value="Publicidad Impresa"> Publicidad Impresa</label>
                            <label><input type="checkbox" name="canales[]" value="Vallas Publicitarias"> Vallas Publicitarias</label>
                            <label><input type="checkbox" name="canales[]" value="Google Ads"> Google Ads</label>
                            <label><input type="checkbox" name="canales[]" value="Redes Sociales"> Redes Sociales</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Redes sociales:</label>
                    <div id="redesContainer" class="multi-select-container">
                        <div class="selected-items" id="redesSelected">
                            <span class="placeholder-text">Selecciona una o varias redes...</span>
                        </div>
                        <div class="dropdown-options" id="redesOptions">
                            <label><input type="checkbox" name="redes[]" value="Facebook"> Facebook</label>
                            <label><input type="checkbox" name="redes[]" value="Instagram"> Instagram</label>
                            <label><input type="checkbox" name="redes[]" value="TikTok"> TikTok</label>
                            <label><input type="checkbox" name="redes[]" value="LinkedIn"> LinkedIn</label>
                            <label><input type="checkbox" name="redes[]" value="Twitter/X"> Twitter/X</label>
                            <label><input type="checkbox" name="redes[]" value="YouTube"> YouTube</label>
                            <label><input type="checkbox" name="redes[]" value="WhatsApp Business"> WhatsApp Business</label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de inicio: <span class="text-danger">*</span></label>
                    <input type="date" name="inicio" id="fechaInicio" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha de fin: <span class="text-danger">*</span></label>
                    <input type="date" name="fin" id="fechaFin" class="form-control" required>
                    <div class="form-text" id="duracionCampania"></div>
                </div>

                <div class="col-12">
                    <label class="form-label">Estrategia de la campaña: <span class="text-danger">*</span></label>
                    <textarea name="estrategia" id="estrategiaCampania" class="form-control" rows="3" maxlength="500" required placeholder="Describe tu estrategia de marketing..."></textarea>
                    <div class="form-text text-end"><span id="charCountEstrategia">0</span> / 500 caracteres</div>
                </div>

                <div class="col-12">
                    <label class="form-label">Comentarios adicionales:</label>
                    <textarea name="comentarios" id="comentariosCampania" class="form-control" rows="2" maxlength="300" placeholder="Notas adicionales o consideraciones..."></textarea>
                    <div class="form-text text-end"><span id="charCountComentarios">0</span> / 300 caracteres</div>
                </div>

                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary px-5" id="submitBtn">
                        <span id="btnText">Guardar Campaña</span>
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
    <script src="assets/js/form-campania-extras.js"></script>
</body>
</html>