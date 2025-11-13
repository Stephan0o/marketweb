<?php
session_start();
require_once __DIR__ . '../../init.php';

if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado");
}

if (!isset($_GET['campania_id'])) {
    die("ID de campaña no especificado");
}

$campania_id = intval($_GET['campania_id']);
$user_id = $_SESSION['usuario_id'];

// Obtener datos de la campaña
$sqlCampania = "SELECT c.*, e.nombre_empresa, e.rubro 
                FROM campañas c 
                INNER JOIN empresas e ON c.empresa_id = e.id 
                WHERE c.id = ? AND e.usuario_id = ?";
$stmt = $conn->prepare($sqlCampania);
$stmt->bind_param("ii", $campania_id, $user_id);
$stmt->execute();
$campania = $stmt->get_result()->fetch_assoc();

if (!$campania) {
    die("Campaña no encontrada");
}

// Obtener estrategia padre y detalles
$sqlEst = "SELECT * FROM estrategias WHERE campania_id = ? ORDER BY generado_en DESC LIMIT 1";
$stmt = $conn->prepare($sqlEst);
$stmt->bind_param("i", $campania_id);
$stmt->execute();
$estrategiaPadre = $stmt->get_result()->fetch_assoc();

if (!$estrategiaPadre) {
    die("No hay estrategias generadas para esta campaña");
}

$sqlDet = "SELECT * FROM detalle_estrategias WHERE estrategia_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($sqlDet);
$stmt->bind_param("i", $estrategiaPadre['id']);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calcular estadísticas
$totalEstrategias = count($detalles);
$finalizadas = 0;
$enCurso = 0;
$pendientes = 0;

foreach ($detalles as $det) {
    if ($det['estado'] == 'Finalizada') $finalizadas++;
    elseif ($det['estado'] == 'En curso') $enCurso++;
    else $pendientes++;
}

$progreso = $totalEstrategias > 0 ? round(($finalizadas / $totalEstrategias) * 100) : 0;

// Limpiar cualquier output previo
ob_end_clean();

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator('MarketWeb');
$pdf->SetAuthor($campania['nombre_empresa']);
$pdf->SetTitle('Estrategias - ' . $campania['nombre_campaña']);
$pdf->SetSubject('Plan de Marketing');

// Configuración de página
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Quitar header y footer por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar fuente con soporte UTF-8
$pdf->SetFont('helvetica', '', 12);

$pdf->AddPage();

// ==================== PORTADA ====================
$html = '
<style>
    body { font-family: helvetica; }
    .portada { text-align: center; margin-top: 60px; }
    .titulo-principal { 
        font-size: 26px; 
        color: #667eea; 
        font-weight: bold; 
        margin-bottom: 20px;
        line-height: 1.4;
    }
    .subtitulo { 
        font-size: 18px; 
        color: #333; 
        margin: 25px 0;
        font-weight: bold;
    }
    .empresa { 
        font-size: 20px; 
        color: #000; 
        font-weight: bold; 
        margin: 30px 0 8px 0;
    }
    .rubro { 
        font-size: 14px; 
        color: #666;
        margin-bottom: 40px;
    }
    .fecha { 
        font-size: 11px; 
        color: #999; 
        margin-top: 50px;
    }
</style>

<div class="portada">
    <div class="titulo-principal">PLAN DE ESTRATEGIAS<br/>DE MARKETING</div>
    <div style="border-top: 3px solid #667eea; width: 60%; margin: 25px auto;"></div>
    <div class="subtitulo">' . htmlspecialchars($campania['nombre_campaña']) . '</div>
    <div class="empresa">' . htmlspecialchars($campania['nombre_empresa']) . '</div>
    <div class="rubro">' . htmlspecialchars($campania['rubro']) . '</div>
    <div class="fecha">Documento generado el ' . date('d/m/Y \a \l\a\s H:i') . ' hrs</div>
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');

// ==================== PÁGINA 2: INFORMACIÓN ====================
$pdf->AddPage();

$html = '
<style>
    .seccion-header { 
        background-color: #667eea;
        color: #ffffff;
        padding: 10px 15px;
        margin: 0 0 20px 0;
        font-size: 15px;
        font-weight: bold;
    }
    .info-row {
        margin: 0 0 15px 0;
        padding: 12px;
        background-color: #f8f9fa;
        border-left: 4px solid #667eea;
    }
    .info-label {
        font-weight: bold;
        color: #667eea;
        font-size: 11px;
        display: block;
        margin-bottom: 4px;
    }
    .info-valor {
        color: #333;
        font-size: 11px;
        line-height: 1.5;
    }
</style>

<div class="seccion-header">INFORMACION DE LA CAMPAÑA</div>

<div class="info-row">
    <span class="info-label">OBJETIVO:</span>
    <span class="info-valor">' . htmlspecialchars($campania['objetivo']) . '</span>
</div>

<div class="info-row">
    <span class="info-label">PUBLICO OBJETIVO:</span>
    <span class="info-valor">' . htmlspecialchars($campania['publico']) . '</span>
</div>

<div class="info-row">
    <span class="info-label">PRESUPUESTO:</span>
    <span class="info-valor">' . htmlspecialchars($campania['presupuesto_marketing']) . '</span>
</div>

<div class="info-row">
    <span class="info-label">CANALES:</span>
    <span class="info-valor">' . htmlspecialchars($campania['canales']) . '</span>
</div>

<div class="info-row">
    <span class="info-label">REDES SOCIALES:</span>
    <span class="info-valor">' . htmlspecialchars($campania['redes']) . '</span>
</div>

<div class="info-row">
    <span class="info-label">DURACION:</span>
    <span class="info-valor">Del ' . date('d/m/Y', strtotime($campania['duracion_inicio'])) . ' al ' . date('d/m/Y', strtotime($campania['duracion_fin'])) . '</span>
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');

// ==================== PROGRESO ====================
$html = '
<style>
    .stats-grid {
        width: 100%;
        margin: 20px 0;
    }
    .stat-item {
        width: 32%;
        display: inline-block;
        text-align: center;
        padding: 15px 5px;
        background-color: #f0f4ff;
        margin: 0 1% 10px 0;
        vertical-align: top;
    }
    .stat-numero {
        font-size: 28px;
        font-weight: bold;
        color: #667eea;
        display: block;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
    }
    .barra-progreso {
        width: 100%;
        height: 25px;
        background-color: #e0e0e0;
        margin: 20px 0;
        position: relative;
    }
    .barra-fill {
        height: 25px;
        background-color: #667eea;
        line-height: 25px;
        color: white;
        text-align: center;
        font-size: 11px;
        font-weight: bold;
    }
    .badges-container {
        text-align: center;
        margin-top: 15px;
    }
    .badge {
        display: inline-block;
        padding: 6px 12px;
        margin: 0 5px;
        font-size: 10px;
        font-weight: bold;
    }
    .badge-pendiente { background-color: #fff3cd; color: #856404; }
    .badge-encurso { background-color: #f8d7da; color: #721c24; }
    .badge-finalizada { background-color: #d4edda; color: #155724; }
</style>

<div class="seccion-header">PROGRESO DE EJECUCION</div>

<div class="stats-grid">
    <div class="stat-item">
        <span class="stat-numero">' . $totalEstrategias . '</span>
        <span class="stat-label">Total Estrategias</span>
    </div>
    <div class="stat-item">
        <span class="stat-numero">' . $finalizadas . '</span>
        <span class="stat-label">Finalizadas</span>
    </div>
    <div class="stat-item">
        <span class="stat-numero">' . $progreso . '%</span>
        <span class="stat-label">Completado</span>
    </div>
</div>

<div class="barra-progreso">
    <div class="barra-fill" style="width: ' . $progreso . '%;">' . $progreso . '% Completado</div>
</div>

<div class="badges-container">
    <span class="badge badge-pendiente">' . $pendientes . ' Pendientes</span>
    <span class="badge badge-encurso">' . $enCurso . ' En Curso</span>
    <span class="badge badge-finalizada">' . $finalizadas . ' Finalizadas</span>
</div>
';

$pdf->writeHTML($html, true, false, true, false, '');

// ==================== ESTRATEGIAS DETALLADAS ====================
$pdf->AddPage();

$html = '
<style>
    .estrategia-box {
        margin: 0 0 18px 0;
        padding: 15px;
        border: 1.5px solid #d0d0d0;
        background-color: #ffffff;
    }
    .estrategia-header {
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #667eea;
    }
    .estrategia-numero {
        color: #667eea;
        font-weight: bold;
        font-size: 12px;
    }
    .estrategia-titulo-text {
        font-size: 12px;
        font-weight: bold;
        color: #333;
    }
    .estrategia-estado {
        float: right;
        padding: 4px 10px;
        font-size: 9px;
        font-weight: bold;
    }
    .estrategia-descripcion {
        font-size: 10px;
        color: #555;
        line-height: 1.6;
        text-align: justify;
    }
</style>

<div class="seccion-header">ESTRATEGIAS DETALLADAS</div>
';

foreach ($detalles as $index => $estr) {
    $estadoTexto = '';
    $estadoClass = '';
    
    switch($estr['estado']) {
        case 'Finalizada':
            $estadoTexto = 'FINALIZADA';
            $estadoClass = 'badge-finalizada';
            break;
        case 'En curso':
            $estadoTexto = 'EN CURSO';
            $estadoClass = 'badge-encurso';
            break;
        default:
            $estadoTexto = 'PENDIENTE';
            $estadoClass = 'badge-pendiente';
    }
    
    $html .= '
    <div class="estrategia-box">
        <div class="estrategia-header">
            <span class="estrategia-numero">#' . ($index + 1) . '</span>
            <span class="estrategia-titulo-text"> ' . htmlspecialchars($estr['titulo']) . '</span>
            <span class="estrategia-estado ' . $estadoClass . '">' . $estadoTexto . '</span>
        </div>
        <div class="estrategia-descripcion">' . nl2br(htmlspecialchars($estr['descripcion'])) . '</div>
    </div>
    ';
}

$pdf->writeHTML($html, true, false, true, false, '');

// Generar PDF
$filename = 'Estrategias_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $campania['nombre_campaña']) . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
exit();