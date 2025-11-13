<?php
session_start();
// 
require_once __DIR__ . '/../../init.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

// Obtener datos de la petición
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$conversationHistory = $input['history'] ?? []; // historial

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje vacío']);
    exit();
}

// Datos usuario
$usuario_id = $_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'];
$rol = $_SESSION['usuario_rol'];

// Datos del user
// empresa
$contextoEmpresa = obtenerContextoEmpresa($conn, $usuario_id);

// campañas
$contextoCampanas = obtenerContextoCampanas($conn, $usuario_id);

// prompt
$systemPrompt = construirPromptSistema($nombre, $rol, $contextoEmpresa, $contextoCampanas);
$promptCompleto = $systemPrompt . "\n\nUsuario: " . $userMessage . "\n\nAsistente:";

// Usar la API key de las variables de entorno
$respuestaBot = llamarGeminiAPI(GEMINI_API_KEY, $promptCompleto);

if ($respuestaBot === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con la IA']);
    exit();
}

$respuestaFormateada = formatearRespuestaHTML($respuestaBot);

// guardar en db
guardarMensaje($conn, $usuario_id, $userMessage, 'user');
guardarMensaje($conn, $usuario_id, $respuestaFormateada, 'bot');

// respuesta hacia user
echo json_encode([
    'success' => true,
    'message' => $respuestaFormateada,
    'timestamp' => date('Y-m-d H:i:s')
]);

//funciones
function obtenerContextoEmpresa($conn, $usuario_id) {
    global $conn;
    $sql = "SELECT nombre_empresa, rubro, anos_mercado, ubicacion, equipo, 
                   productos, descripcion, diferenciador 
            FROM empresas WHERE usuario_id = ? LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $empresa = $result->fetch_assoc();
        $stmt->close();
        return [
            'tiene' => true,
            'datos' => $empresa
        ];
    }
    
    $stmt->close();
    return ['tiene' => false];
}

//campañas
function obtenerContextoCampanas($conn, $usuario_id) {
    $sql = "SELECT c.nombre_campaña, c.objetivo, c.publico, c.presupuesto_marketing, 
                   c.canales, c.redes, c.duracion_inicio, c.duracion_fin
            FROM campañas c
            INNER JOIN empresas e ON c.empresa_id = e.id
            WHERE e.usuario_id = ?
            ORDER BY c.creado_en DESC
            LIMIT 3";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $campanas = [];
    while ($row = $result->fetch_assoc()) {
        $campanas[] = $row;
    }
    
    $stmt->close();
    return [
        'tiene' => count($campanas) > 0,
        'cantidad' => count($campanas),
        'datos' => $campanas
    ];
}

//prompt con cotexto del user
function construirPromptSistema($nombre, $rol, $contextoEmpresa, $contextoCampanas) {
    $prompt = "Eres un asistente experto en marketing digital para la plataforma MarketWeb. ";
    $prompt .= "Tu nombre es 'Asistente MarketWeb' y ayudas a usuarios a crear estrategias de marketing efectivas.\n\n";
    
    $prompt .= "INFORMACIÓN DEL USUARIO:\n";
    $prompt .= "- Nombre: $nombre\n";
    $prompt .= "- Rol: $rol\n";
    
    // Contexto de empresa
    if ($contextoEmpresa['tiene']) {
        $empresa = $contextoEmpresa['datos'];
        $prompt .= "\nEMPRESA DEL USUARIO:\n";
        $prompt .= "- Nombre: " . ($empresa['nombre_empresa'] ?? 'No especificado') . "\n";
        $prompt .= "- Rubro: " . ($empresa['rubro'] ?? 'No especificado') . "\n";
        $prompt .= "- Años en el mercado: " . ($empresa['anos_mercado'] ?? 'No especificado') . "\n";
        $prompt .= "- Ubicación: " . ($empresa['ubicacion'] ?? 'No especificado') . "\n";
    } else {
        $prompt .= "\n⚠️ El usuario AÚN NO ha registrado su empresa.\n";
        $prompt .= "Si pregunta sobre campañas o estrategias, recomiéndale primero registrar su empresa.\n";
    }
    
    // Contexto de campañas
    if ($contextoCampanas['tiene']) {
        $prompt .= "\nCAMPAÑAS ACTIVAS (" . $contextoCampanas['cantidad'] . "):\n";
        foreach ($contextoCampanas['datos'] as $index => $campana) {
            $prompt .= ($index + 1) . ". " . $campana['nombre_campaña'] . "\n";
            $prompt .= "   - Objetivo: " . $campana['objetivo'] . "\n";
            $prompt .= "   - Público: " . $campana['publico'] . "\n";
        }
    }
    
    $prompt .= "\n INSTRUCCIONES CRÍTICAS DE FORMATO:\n";
    $prompt .= "1. SÉ BREVE: Máximo 2-3 párrafos cortos\n";
    $prompt .= "2. USA EMOJIS: Coloca un emoji relevante al inicio de cada punto\n";
    $prompt .= "3. USA LISTAS: Para múltiples ideas usa viñetas con guiones (-)\n";
    $prompt .= "4. ESTRUCTURA:\n";
    $prompt .= "   - Saludo breve (1 línea)\n";
    $prompt .= "   - Respuesta principal (1-2 párrafos)\n";
    $prompt .= "   - Si hay varios puntos, usa lista con guiones (-)\n";
    $prompt .= "   - Cierre opcional (1 línea)\n";
    $prompt .= "5. NO uses HTML, usa texto plano con formato markdown simple\n";
    $prompt .= "6. EJEMPLO DE FORMATO CORRECTO:\n\n";
    $prompt .= "¡Hola! 👋\n\n";
    $prompt .= "Para tu empresa en el rubro de tecnología, te recomiendo:\n\n";
    $prompt .= "- 💡 Enfocarte en LinkedIn y Facebook\n";
    $prompt .= "- 🎯 Crear contenido educativo\n";
    $prompt .= "- 📊 Usar anuncios segmentados\n\n";
    $prompt .= "¿Necesitas ayuda con algo más específico?\n\n";
    
    return $prompt;
}

// API
function llamarGeminiAPI($apiKey, $prompt) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey";
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800,
            "topP" => 0.85,
            "topK" => 40
        ],
        "safetySettings" => [
            [
                "category" => "HARM_CATEGORY_HARASSMENT",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ],
            [
                "category" => "HARM_CATEGORY_HATE_SPEECH",
                "threshold" => "BLOCK_MEDIUM_AND_ABOVE"
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Error en Gemini API: " . $response);
        return false;
    }
    
    $responseData = json_decode($response, true);
    
    if (isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
    
    return false;
}

// Convertir respuesta a HTML
function formatearRespuestaHTML($texto) {
    // Limpiar espacios extras y normalizar saltos de línea
    $texto = trim($texto);
    $texto = str_replace("\r\n", "\n", $texto);
    
    // Separar el texto en bloques
    $bloques = preg_split('/\n\n+/', $texto);
    $html = '';
    
    foreach ($bloques as $bloque) {
        $bloque = trim($bloque);
        if (empty($bloque)) continue;
        
        // Detectar símbolos
        $lineas = explode("\n", $bloque);
        $esLista = false;
        
        foreach ($lineas as $linea) {
            if (preg_match('/^\s*[-*•]\s+/', $linea) || preg_match('/^\s*\p{So}\s+/u', $linea)) {
                $esLista = true;
                break;
            }
        }
        
        if ($esLista) {
            // Procesar como lista
            $html .= '<ul class="bot-list">';
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;
                
                // Remover guiones, asteriscos
                $linea = preg_replace('/^[-*•]\s+/', '', $linea);
                $html .= '<li>' . nl2br(htmlspecialchars($linea)) . '</li>';
            }
            $html .= '</ul>';
        } else {
            // Procesar como párrafo
            $bloque = nl2br(htmlspecialchars($bloque));
            
            // Convertir negritas
            $bloque = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $bloque);
            
            // Convertir cursivas
            $bloque = preg_replace('/\*([^\*]+?)\*/', '<em>$1</em>', $bloque);
            
            $html .= '<p>' . $bloque . '</p>';
        }
    }
    
    // Si no se generó HTML, envolver todo en un párrafo
    if (empty($html)) {
        $html = '<p>' . nl2br(htmlspecialchars($texto)) . '</p>';
    }
    
    return $html;
}

// guardar mensaje en la db
function guardarMensaje($conn, $usuario_id, $mensaje, $rol) {
    $sql = "INSERT INTO chatbot_conversaciones (usuario_id, mensaje, rol) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $usuario_id, $mensaje, $rol);
    $stmt->execute();
    $stmt->close();
}
?>