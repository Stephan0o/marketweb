<?php
session_start();
// 
require_once __DIR__ . '/../../init.php';

header('Content-Type: application/json');

// Validar login
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Inicia sesión.']);
    exit();
}

$user_id = $_SESSION['usuario_id'];

// Validar campania_id
if (!isset($_GET['campania_id']) || !is_numeric($_GET['campania_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de campaña inválido.']);
    exit();
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
    echo json_encode(['success' => false, 'message' => 'Campaña no encontrada o no tienes permisos.']);
    exit();
}

$data = $resCamp->fetch_assoc();

$empresa = [
    'id' => $data['empresa_id'],
    'nombre_empresa' => $data['nombre_empresa'],
    'rubro' => $data['rubro'],
    'anos_mercado' => $data['anos_mercado'],
    'ubicacion' => $data['ubicacion'],
    'equipo' => $data['equipo'],
    'competencia' => $data['competencia'],
    'diferenciador' => $data['diferenciador'],
    'productos' => $data['productos'],
    'descripcion' => $data['descripcion']
];

$campania = [
    'id' => $data['id'],
    'nombre_campaña' => $data['nombre_campaña'],
    'objetivo' => $data['objetivo'],
    'publico' => $data['publico'],
    'presupuesto_marketing' => $data['presupuesto_marketing'],
    'canales' => $data['canales'],
    'redes' => $data['redes'],
    'duracion_inicio' => $data['duracion_inicio'],
    'duracion_fin' => $data['duracion_fin'],
    'comentarios' => $data['comentarios']
];

$nombre_empresa = $empresa['nombre_empresa'];

// Verificar si ya existen estrategias
$sqlCheck = "SELECT * FROM estrategias WHERE campania_id = ? LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $campania_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Las estrategias ya existen.']);
    exit();
}

// Prompt
$prompt = "Eres un estratega experto en marketing con un tono profesional y experto, pero usando un lenguaje claro, sencillo y directo, como si se lo explicaras a un emprendedor sin formación técnica. Evita jerga especializada. Genera EXACTAMENTE 3 estrategias de marketing profesionales en formato JSON para:

EMPRESA: '{$nombre_empresa}'
- Rubro: {$empresa['rubro']}
- Años en mercado: {$empresa['anos_mercado']}
- Ubicación: {$empresa['ubicacion']}
- Equipo: {$empresa['equipo']} personas
- Competencia: {$empresa['competencia']}
- Diferenciador: {$empresa['diferenciador']}
- Productos/Servicios: {$empresa['productos']}
- Descripción: {$empresa['descripcion']}

CAMPAÑA: '{$campania['nombre_campaña']}'
- Objetivo: {$campania['objetivo']}
- Público: {$campania['publico']}
- Presupuesto: {$campania['presupuesto_marketing']}
- Canales: {$campania['canales']}
- Redes: {$campania['redes']}
- Duración: {$campania['duracion_inicio']} a {$campania['duracion_fin']}
- Requisitos: {$campania['comentarios']}

Cada estrategia DEBE incluir explícitamente:
1. Análisis del entorno externo (tendencias del sector, cambios en consumo)
2. Análisis de competidores (fortalezas, ofertas, desempeño)
3. Métodos de medición de satisfacción del cliente
4. Estrategia de precios con análisis de rentabilidad
5. Respuesta a necesidades específicas de clientes
6. Adaptación a capacidades de la empresa (equipo, presupuesto)
7. Mecanismos para capturar feedback y críticas
8. Segmentación detallada del público objetivo
9. Detección de necesidades no expresadas
10. Diferenciación clara vs competencia
11. Innovación en productos/servicios
12. Mecanismos de adaptación según demanda del mercado

FORMATO REQUERIDO - JSON válido, sin texto adicional:
[
  {
    \"titulo\": \"Título conciso orientado a resultados\",
    \"descripcion\": \"Descripción de un ejemplo práctico de implementación en 100 palabras como MAXIMO. El texto debe ser en prosa continua, sin negritas, títulos o subtítulos internos, donde cada idea se enlace con la siguiente mediante un punto y seguido, manteniendo un flujo cohesivo y profesional. Debe integrar de forma indirecta los 12 indicadores.\",
  },
  {
    \"titulo\": \"Título de segunda estrategia\",
    \"descripcion\": \"...\"
  },
  {
    \"titulo\": \"Título de tercera estrategia\",
    \"descripcion\": \"...\"
  }
]

Genera SOLO el JSON, sin explicaciones adicionales:";

// Usar la API key de las variables de entorno
$apiKey = GEMINI_API_KEY;
$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey");

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$dataAPI = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.6,
        "maxOutputTokens" => 3000,
        "topP" => 0.8,
        "topK" => 40
    ]
];

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataAPI));
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

// Validar errores de cURL
if ($curlError) {
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Error cURL: ".$curlError."\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error de conexión con la API.']);
    exit();
}

$responseData = json_decode($response, true);

// Validar respuesta de la API
if (isset($responseData['error'])) {
    $errorMsg = $responseData['error']['message'] ?? 'Error desconocido de la API';
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Error API: ".$errorMsg."\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error de la API: ' . $errorMsg]);
    exit();
}

// Extraer respuesta de gemini
$texto = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? "";

if (empty($texto)) {
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Respuesta vacía. Full response: ".json_encode($responseData)."\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'No se recibió respuesta de la API.']);
    exit();
}

// Log de la respuesta cruda para debugging
file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Respuesta cruda:\n".$texto."\n\n", FILE_APPEND);

// Limpieza de respuestas
$textoLimpio = trim($texto);
// Remover bloques de código markdown
$textoLimpio = preg_replace('/^```(json)?[\r\n]+/im', '', $textoLimpio);
$textoLimpio = preg_replace('/[\r\n]+```$/im', '', $textoLimpio);
$textoLimpio = trim($textoLimpio);

// Si empieza con texto antes del JSON, intentar extraer solo el JSON
if (!preg_match('/^\[/', $textoLimpio)) {
    if (preg_match('/(\[[\s\S]*\])/m', $textoLimpio, $matches)) {
        $textoLimpio = $matches[1];
    }
}

// Log del JSON limpio
file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - JSON limpio:\n".$textoLimpio."\n\n", FILE_APPEND);

// Parsear como JSON
$estrategias = json_decode($textoLimpio, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $jsonError = json_last_error_msg();
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Error JSON: ".$jsonError."\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error al procesar JSON: ' . $jsonError]);
    exit();
}

if (!$estrategias || !is_array($estrategias) || count($estrategias) !== 3) {
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Estructura inválida. Estrategias: ".json_encode($estrategias)."\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Formato de estrategias inválido. Se esperaban 3 estrategias.']);
    exit();
}

// Insert en estrategias (padre)
$sqlInsert = "INSERT INTO estrategias (empresa_id, campania_id, generado_en) VALUES (?, ?, NOW())";
$stmtInsert = $conn->prepare($sqlInsert);
$stmtInsert->bind_param("ii", $empresa['id'], $campania_id);
$stmtInsert->execute();
$estrategia_id = $stmtInsert->insert_id;

if (!$estrategia_id) {
    echo json_encode(['success' => false, 'message' => 'Error al crear registro de estrategia.']);
    exit();
}

// Insertar las 3 estrategias
$insertados = 0;
foreach ($estrategias as $estr) {
    if (!isset($estr['titulo']) || !isset($estr['descripcion'])) {
        continue;
    }
    
    $titulo = trim($estr['titulo']);
    $descripcion = trim($estr['descripcion']);
    
    if (empty($titulo) || empty($descripcion)) {
        continue;
    }

    $sqlDet = "INSERT INTO detalle_estrategias (estrategia_id, titulo, descripcion, estado) 
               VALUES (?, ?, ?, 'Pendiente')";
    $stmtDet = $conn->prepare($sqlDet);
    $stmtDet->bind_param("iss", $estrategia_id, $titulo, $descripcion);
    
    if ($stmtDet->execute()) {
        $insertados++;
    }
}

if ($insertados === 3) {
    echo json_encode(['success' => true, 'message' => 'Estrategias generadas correctamente.']);
} else {
    file_put_contents("log_estrategias.txt", date('Y-m-d H:i:s')." - Solo se insertaron $insertados estrategias de 3\n\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => "Solo se pudieron generar $insertados de 3 estrategias."]);
}
?>