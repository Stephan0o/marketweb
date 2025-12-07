<?php
session_start();
require_once __DIR__ . '../../init-light.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];

// Obtener empresa
$sqlEmpresa = "SELECT * FROM empresas WHERE usuario_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sqlEmpresa);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultEmpresa = $stmt->get_result();
$empresa = $resultEmpresa->fetch_assoc();

if (!$empresa) {
    echo "<p>No has registrado ninguna empresa aún.</p>";
    exit();
}

$empresa_id = $empresa['id'];

// Obtener campañas
$sqlCampanias = "SELECT * FROM campañas WHERE empresa_id = ? ORDER BY creado_en DESC";
$stmt2 = $conn->prepare($sqlCampanias);
$stmt2->bind_param("i", $empresa_id);
$stmt2->execute();
$resultCampanias = $stmt2->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estrategias de <?php echo htmlspecialchars($empresa['nombre_empresa']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/estrategias.css" rel="stylesheet">
    <link href="assets/css/select.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">🚀 Estrategias de Marketing</h1>
                    <p class="text-white mb-0" style="font-size: 1.1rem;">
                        <strong><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></strong>
                    </p>
                </div>
                <a href="panel.php" class="btn btn-light">⬅ Volver al Panel</a>
            </div>
        </div>
    </div>

    <?php if ($resultCampanias->num_rows === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📭</div>
            <h3>No hay campañas registradas</h3>
            <p class="text-muted">Aún no has creado campañas para esta empresa.</p>
            <a href="form_campania.php" class="btn btn-primary mt-3">➕ Crear Primera Campaña</a>
        </div>
    <?php else: ?>
        <div class="accordion" id="accordionCampanias">
        <?php while ($campania = $resultCampanias->fetch_assoc()): ?>
            <?php
            // Obtener estrategia padre
            $sqlEst = "SELECT * FROM estrategias WHERE campania_id = ? ORDER BY generado_en DESC LIMIT 1";
            $stmt3 = $conn->prepare($sqlEst);
            $stmt3->bind_param("i", $campania['id']);
            $stmt3->execute();
            $resEst = $stmt3->get_result();
            $estrategiaPadre = $resEst->fetch_assoc();

            $detalles = [];
            $totalEstrategias = 0;
            $finalizadas = 0;
            $enCurso = 0;
            $pendientes = 0;

            if ($estrategiaPadre) {
                $sqlDet = "SELECT * FROM detalle_estrategias WHERE estrategia_id = ? ORDER BY id ASC";
                $stmt4 = $conn->prepare($sqlDet);
                $stmt4->bind_param("i", $estrategiaPadre['id']);
                $stmt4->execute();
                $resDet = $stmt4->get_result();
                $detalles = $resDet->fetch_all(MYSQLI_ASSOC);
                $totalEstrategias = count($detalles);
                foreach ($detalles as $det) {
                    if ($det['estado'] == 'Finalizada') $finalizadas++;
                    elseif ($det['estado'] == 'En curso') $enCurso++;
                    else $pendientes++;
                }
            }
            
            // Calcular porcentaje de progreso
            $progreso = $totalEstrategias > 0 ? round(($finalizadas / $totalEstrategias) * 100) : 0;
            ?>
            
            <div class="campania-card">
                <div class="campania-header" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapse<?= $campania['id'] ?>" 
                     aria-expanded="false">
                    <div class="campania-info">
                        <h3 class="campania-titulo">
                            📢 <?= htmlspecialchars($campania['nombre_campaña']) ?>
                            <span class="chevron-icon">▼</span>
                        </h3>
                        <div class="campania-objetivo">
                            <strong>Objetivo:</strong> <?= htmlspecialchars($campania['objetivo']) ?> 
                            | <strong>Público:</strong> <?= htmlspecialchars($campania['publico']) ?>
                        </div>
                    </div>
                    
                    <?php if ($estrategiaPadre): ?>
                    <div class="progress-section" onclick="event.stopPropagation();">
                        <div class="progress-label">
                            <span>Progreso</span>
                            <span><strong><?= $finalizadas ?>/<?= $totalEstrategias ?></strong></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: <?= $progreso ?>%" 
                                 aria-valuenow="<?= $progreso ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small style="opacity: 0.9; margin-top: 5px; display: block;">
                            <?= $progreso ?>% completado
                        </small>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!$estrategiaPadre): ?>
                    <div class="alert-estrategias alert alert-warning">
                        ⚠️ Esta campaña aún no tiene estrategias generadas
                        <a href="../backend/API/loader.php?campania_id=<?= $campania['id'] ?>"
                           class="btn btn-generar ms-3">✨ Generar Estrategias Ahora</a>
                    </div>
                <?php else: ?>
                    <div class="alert-estrategias alert alert-success mb-0">
                        ✅ Estrategias generadas el <?= date('d/m/Y H:i', strtotime($estrategiaPadre['generado_en'])) ?>
                    </div>

                    <div id="collapse<?= $campania['id'] ?>" 
                         class="accordion-collapse collapse" 
                         aria-labelledby="heading<?= $campania['id'] ?>">
                        <div class="accordion-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">📋 Lista de Estrategias</h5>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-warning"><?= $pendientes ?> Pendientes</span>
                                    <span class="badge bg-danger"><?= $enCurso ?> En Curso</span>
                                    <span class="badge bg-success"><?= $finalizadas ?> Finalizadas</span>
                                </div>
                            </div>

                            <?php foreach ($detalles as $index => $estr): ?>
                                <div class="estrategia-card">
                                    <div class="estrategia-titulo">
                                        <span style="color: #667eea;">#{<?= $index + 1 ?>}</span>
                                        <?= htmlspecialchars($estr['titulo']) ?>
                                    </div>
                                    <p class="estrategia-descripcion">
                                        <?= nl2br(htmlspecialchars(trim($estr['descripcion']))) ?>
                                    </p>
                                    
                                    <div class="estrategia-controls">
                                        <span style="font-weight: 600; color: #5a5a5a;">Estado:</span>
                                        <select class="estado-select" 
                                                data-detalle-id="<?= $estr['id'] ?>" 
                                                data-campania-id="<?= $campania['id'] ?>"
                                                onchange="actualizarEstado(this)">
                                            <option value="Pendiente" <?= $estr['estado']=="Pendiente" ? "selected" : "" ?>>⏳ Pendiente</option>
                                            <option value="En curso" <?= $estr['estado']=="En curso" ? "selected" : "" ?>>🔄 En curso</option>
                                            <option value="Finalizada" <?= $estr['estado']=="Finalizada" ? "selected" : "" ?>>✅ Finalizada</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <!-- Botón PDF -->
                            <div class="text-center mt-4 pt-3" style="border-top: 2px solid #e0e0e0;">
                                <a href="../includes/exportar_pdf.php?campania_id=<?= $campania['id'] ?>" 
                                   class="btn btn-danger btn-lg" 
                                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; font-weight: bold; border-radius: 8px;">
                                    📄 Descargar en PDF
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function actualizarEstado(select) {
    let id = select.dataset.detalleId;
    let campaniaId = select.dataset.campaniaId;
    let estado = select.value;

    fetch("../backend/updates/actualizar_estado.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id + "&estado=" + estado
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
        // Recargar la página para actualizar la barra de progreso
        location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
    });
}
</script>
</body>
</html>