<?php
session_start();
require_once __DIR__ . '../../init-light.php';

// Validar login
if (!isset($_SESSION['usuario_id'])) {
    die("Acceso denegado. Inicia sesión.");
}

$user_id = $_SESSION['usuario_id'];

// Validar campania_id
if (!isset($_GET['campania_id']) || !is_numeric($_GET['campania_id'])) {
    die("ID de campaña inválido.");
}
$campania_id = intval($_GET['campania_id']);

// Obtener campaña y validar
$sqlCamp = "SELECT c.*, e.* 
            FROM campañas c 
            JOIN empresas e ON c.empresa_id = e.id 
            WHERE c.id = ? AND e.usuario_id = ?";
$stmt = $conn->prepare($sqlCamp);
$stmt->bind_param("ii", $campania_id, $user_id);
$stmt->execute();
$resCamp = $stmt->get_result();

if ($resCamp->num_rows === 0) {
    die("Campaña no encontrada o no tienes permisos.");
}

$data = $resCamp->fetch_assoc();

$campania = [
    'id' => $data['id'],
    'nombre_campaña' => $data['nombre_campaña']
];

// Obtener estrategia
$sqlCheck = "SELECT * FROM estrategias WHERE campania_id = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $campania_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$estrategiaExistente = $resCheck->fetch_assoc();

if (!$estrategiaExistente) {
    die("No se encontraron estrategias para esta campaña.");
}

$estrategia_id = $estrategiaExistente['id'];

// Obtener estrategias detalle
$sqlDet = "SELECT * FROM detalle_estrategias WHERE estrategia_id = ?";
$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("i", $estrategia_id);
$stmtDet->execute();
$resDet = $stmtDet->get_result();
$detalles = $resDet->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estrategias Generadas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/custom.css">
    <style>
        .strategy-card {
            border-left: 6px solid var(--primary);
            transition: all 0.3s ease;
        }
        .strategy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

<div class="container py-5">
    <h1 class="text-center mb-4">🚀 Estrategias para <?= htmlspecialchars($campania['nombre_campaña']) ?></h1>

    <div class="alert alert-success text-center fw-semibold">
        ✅ Estrategias generadas exitosamente
    </div>

    <!-- Estrategias -->
    <div class="row g-4">
        <?php foreach ($detalles as $d): ?>
            <div class="col-md-6">
                <div class="card strategy-card p-3 h-100">
                    <h5><?= htmlspecialchars($d['titulo']) ?></h5>
                    <p><?= nl2br(htmlspecialchars($d['descripcion'])) ?></p>
                    <span class="badge bg-warning"><?= $d['estado'] ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Botón volver -->
    <div class="text-center mt-5">
        <a href="estrategias.php" class="btn btn-secondary btn-lg ms-2">⬅ Volver</a>
    </div>
</div>

</body>
</html>