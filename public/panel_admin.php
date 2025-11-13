<?php
require_once "../backend/config/db.php";
require_once "../includes/middleware.php"; 
verificarRol("admin");

// Filtros
$filtro_empresa = $_GET["empresa_id"] ?? null;
$fecha_inicio = $_GET["fecha_inicio"] ?? null;
$fecha_fin = $_GET["fecha_fin"] ?? null;

// Empresas para el select
$empresas = $conn->query("SELECT id, nombre_empresa FROM empresas");

// Consulta base
$sql = "
    SELECT est.id AS estrategia_id, est.contenido, est.generado_en,
           emp.nombre_empresa AS empresa
    FROM estrategias est
    JOIN empresas emp ON emp.id = est.empresa_id
    WHERE 1=1
";

// Aplicar filtros dinámicamente
if ($filtro_empresa) {
    $sql .= " AND emp.id = " . intval($filtro_empresa);
}
if ($fecha_inicio && $fecha_fin) {
    $sql .= " AND DATE(est.generado_en) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$sql .= " ORDER BY est.generado_en DESC";
$result = $conn->query($sql);
?>

<h2>📊 Historial de Estrategias</h2>

<form method="GET">
    <label>Empresa:</label>
    <select name="empresa_id">
        <option value="">Todas</option>
        <?php while($row = $empresas->fetch_assoc()): ?>
            <option value="<?= $row['id'] ?>" <?= $filtro_empresa == $row['id'] ? 'selected' : '' ?>>
                <?= $row['nombre_empresa'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Desde:</label>
    <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">

    <label>Hasta:</label>
    <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">

    <button type="submit">Filtrar</button>
</form>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Empresa</th>
        <th>Contenido</th>
        <th>Fecha</th>
        <th>Acción</th>
    </tr>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
                    <td><?= htmlspecialchars($row['empresa']) ?></td>
                    <td><?= nl2br(htmlspecialchars(substr($row['contenido'], 0, 100))) ?>...</td>
                    <td><?= $row['generado_en'] ?></td>
                    <td><a href="exportar_pdf.php?id=<?= $row['estrategia_id'] ?>" class="btn btn-sm btn-danger">📥 PDF</a></td>
    </tr>
    <?php endwhile; ?>
</table>

