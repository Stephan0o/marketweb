<?php
session_start();

require_once __DIR__ . '/../../init.php';

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';
$conversationHistory = $input['history'] ?? [];

if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensaje vacío']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'];
$rol = $_SESSION['usuario_rol'];

// DETECCIÓN DE INTENCIÓN
function detectarIntencion($mensaje) {
    $mensaje_lower = strtolower($mensaje);
    
    $intenciones = [
        'ideas' => ['idea', 'dame ideas', 'sugiere', 'qué puedo', 'cómo podría', 'propuesta'],
        'analisis' => ['analiza', 'análisis', 'evalúa', 'cómo está', 'rendimiento', 'resultados', 'comparar'],
        'estrategia' => ['estrategia', 'plan', 'cómo hacer', 'enfoque', 'tactic', 'objetivo'],
        'contenido' => ['contenido', 'posts', 'texto', 'copy', 'describe', 'redacta', 'mensaje', 'eslogan'],
        'publico' => ['público', 'audience', 'target', 'cliente', 'usuario', 'demogr'],
        'presupuesto' => ['presupuesto', 'costo', 'inversión', 'precio', 'cuánto', 'gasto', 'rentabilidad'],
        'redes' => ['red social', 'instagram', 'facebook', 'linkedin', 'tiktok', 'youtube', 'canal'],
        'consejo' => ['me aconsejas', 'qué hago', 'debería', 'recomendación', 'crees que'],
        'ubicacion' => ['dónde', 'ubicación', 'localización', 'dirección', 'zona', 'cercano', 'lugar'],
        'proveedores' => ['proveedor', 'supplier', 'distribuidor', 'mayorista', 'b2b'],
        'actividades' => ['actividad', 'evento', 'lugar', 'dónde hacer', 'campaña offline']
    ];
    
    foreach ($intenciones as $intencion => $palabras_clave) {
        foreach ($palabras_clave as $palabra) {
            if (strpos($mensaje_lower, $palabra) !== false) {
                return $intencion;
            }
        }
    }
    
    return 'general';
}

// CREAR RESUMEN CONTEXTUAL INTELIGENTE
function crearResumenContexto($conversationHistory, $usuarioData, $empresaData, $campanasData) {
    $resumen = [];
    
    $temas_mencionados = [];
    $ultimos_5 = array_slice($conversationHistory, -10);
    
    foreach ($ultimos_5 as $msg) {
        if ($msg['role'] === 'user') {
            $intencion = detectarIntencion($msg['content']);
            $temas_mencionados[] = $intencion;
        }
    }
    
    $tema_dominante = array_count_values($temas_mencionados);
    arsort($tema_dominante);
    $tema_principal = array_key_first($tema_dominante) ?? 'general';
    
    $resumen['tema_conversacion'] = $tema_principal;
    $resumen['cantidad_mensajes'] = count($ultimos_5);
    
    if ($empresaData['tiene']) {
        $empresa = $empresaData['datos'];
        $resumen['empresa_info'] = [
            'nombre' => $empresa['nombre_empresa'] ?? '',
            'rubro' => $empresa['rubro'] ?? '',
            'diferenciador' => substr($empresa['diferenciador'] ?? '', 0, 100)
        ];
    }
    
    if ($campanasData['tiene']) {
        $resumen['campanas_activas'] = $campanasData['cantidad'];
        $resumen['ultimo_canal'] = $campanasData['datos'][0]['canales'] ?? 'No especificado';
    }
    
    return $resumen;
}

// OBTENER INSTRUCCIONES POR INTENCIÓN
function obtenerInstruccionesPorIntencion($intencion) {
    $instrucciones = [
        'ideas' => "Sé CREATIVO pero práctico. Proporciona 2-3 ideas específicas y accionables. Usa un tono entusiasta.",
        'analisis' => "Sé CRÍTICO pero constructivo. Analiza qué funciona/no funciona. Proporciona 1 recomendación concreta de mejora.",
        'estrategia' => "Sé ESTRUCTURADO. Responde con: Objetivo + Acciones clave (máx 3) + Métrica de éxito. Tono profesional.",
        'contenido' => "Sé DIRECTO. Si pide redacción, hazla BREVE (máx 2 líneas si es social). Si es análisis, explica por qué funciona.",
        'publico' => "Sé ESPECÍFICO. Describe el público con 2-3 características clave. Enlaza con los datos de la empresa si existen.",
        'presupuesto' => "Sé REALISTA. Proporciona rangos, no números exactos. Relaciona inversión con ROI posible.",
        'redes' => "Sé RECOMENDADOR. Sugiere qué red específica según el objetivo/público. Explica brevemente por qué.",
        'consejo' => "Sé MENTOR. Hazle preguntas reflexivas: '¿Has considerado...' o proporciona perspectiva diferente.",
        'ubicacion' => "Sé GEOESPECÍFICO. Usa la ubicación exacta de la empresa para dar recomendaciones locales REALES. Menciona puntos de referencia, zonas cercanas, barrios estratégicos.",
        'proveedores' => "Sé COMERCIAL. Recomienda tipos de proveedores reales según el rubro y ubicación de la empresa. Sé específico con nombres de categorías o tipos de proveedores conocidos.",
        'actividades' => "Sé LOGÍSTICO. Proporciona lugares específicos cercanos a la ubicación de la empresa donde pueden realizar campañas. Menciona parques, plazas, centros comerciales reales de la zona.",
        'general' => "Responde de forma balanceada, útil y concisa."
    ];
    
    return $instrucciones[$intencion] ?? $instrucciones['general'];
}

// OBTENER DATOS DE LA EMPRESA
$contextoEmpresa = obtenerContextoEmpresa($conn, $usuario_id);
$contextoCampanas = obtenerContextoCampanas($conn, $usuario_id);

// CREAR RESUMEN CONTEXTUAL
$resumenContexto = crearResumenContexto($conversationHistory, [], $contextoEmpresa, $contextoCampanas);

// DETECTAR INTENCIÓN
$intencion = detectarIntencion($userMessage);
$instruccionesIntencion = obtenerInstruccionesPorIntencion($intencion);

// CONSTRUIR PROMPT 
$systemPrompt = construirPromptSistemaV2Mejorado(
    $nombre, 
    $rol, 
    $contextoEmpresa, 
    $contextoCampanas,
    $resumenContexto,
    $instruccionesIntencion,
    $intencion
);

$promptCompleto = $systemPrompt . "\n\nUsuario: " . $userMessage . "\n\nAsistente:";

// LLAMAR API
$respuestaBot = llamarGeminiAPI(GEMINI_API_KEY, $promptCompleto);

if ($respuestaBot === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al conectar con la IA']);
    exit();
}

$respuestaFormateada = formatearRespuestaHTML($respuestaBot);

// AGREGAR BOTONES SI FALTA CONTEXTO
$botonesAdicionales = '';

if (!$contextoEmpresa['tiene']) {
    $botonesAdicionales .= '<div class="chatbot-action-buttons">';
    $botonesAdicionales .= '<a href="../../pages/form_empresa.php" class="chatbot-btn-action chatbot-btn-empresa">';
    $botonesAdicionales .= '📝 Registrar mi Empresa';
    $botonesAdicionales .= '</a>';
    $botonesAdicionales .= '</div>';
} elseif (!$contextoCampanas['tiene']) {
    $botonesAdicionales .= '<div class="chatbot-action-buttons">';
    $botonesAdicionales .= '<a href="../../pages/form_campania.php" class="chatbot-btn-action chatbot-btn-campania">';
    $botonesAdicionales .= '🎯 Crear mi Primera Campaña';
    $botonesAdicionales .= '</a>';
    $botonesAdicionales .= '</div>';
}

$respuestaFormateada .= $botonesAdicionales;

// Guardar en BD
guardarMensaje($conn, $usuario_id, $userMessage, 'user');
guardarMensaje($conn, $usuario_id, $respuestaFormateada, 'bot');

echo json_encode([
    'success' => true,
    'message' => $respuestaFormateada,
    'timestamp' => date('Y-m-d H:i:s')
]);

// FUNCIONEs
function obtenerContextoEmpresa($conn, $usuario_id) {
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
        return ['tiene' => true, 'datos' => $empresa];
    }
    
    $stmt->close();
    return ['tiene' => false];
}

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
    return ['tiene' => count($campanas) > 0, 'cantidad' => count($campanas), 'datos' => $campanas];
}

// PROMPT 
function construirPromptSistemaV2Mejorado($nombre, $rol, $contextoEmpresa, $contextoCampanas, $resumenContexto, $instruccionesIntencion, $intencion) {
    $prompt = "Eres un asistente experto en marketing digital para MarketWeb. ";
    $prompt .= "Tu nombre es 'Asistente MarketWeb' y estas diseñadp para apoyar a emprendedores en todas las etapas de su negocio. desde generar estrategias de marketing, hasta la entrega de información sobre conceptos o datos que el usuario desconozca..\n\n";
    
    $prompt .= "INFORMACIÓN DEL USUARIO:\n";
    $prompt .= "- Nombre: $nombre\n";
    $prompt .= "- Rol: $rol\n";
    $prompt .= "- Tipo de consulta actual: " . strtoupper($intencion) . "\n\n";
    
    // Contexto COMPLETO de empresa
    if ($contextoEmpresa['tiene']) {
        $empresa = $contextoEmpresa['datos'];
        $prompt .= "INFORMACIÓN COMPLETA DE LA EMPRESA:\n";
        $prompt .= "- Nombre: " . ($empresa['nombre_empresa'] ?? 'N/A') . "\n";
        $prompt .= "- Rubro/Sector: " . ($empresa['rubro'] ?? 'N/A') . "\n";
        $prompt .= "- Ubicación EXACTA: " . ($empresa['ubicacion'] ?? 'N/A') . "\n";
        $prompt .= "- Años en el mercado: " . ($empresa['anos_mercado'] ?? 'N/A') . "\n";
        $prompt .= "- Descripción: " . ($empresa['descripcion'] ?? 'N/A') . "\n";
        $prompt .= "- Productos/Servicios: " . ($empresa['productos'] ?? 'N/A') . "\n";
        $prompt .= "- Diferenciador clave: " . ($empresa['diferenciador'] ?? 'N/A') . "\n";
        $prompt .= "- Equipo: " . ($empresa['equipo'] ?? 'N/A') . "\n\n";
        
        // Instrucciones específicas si es consulta de ubicación
        if ($intencion === 'ubicacion' || $intencion === 'actividades' || $intencion === 'proveedores') {
            $prompt .= "IMPORTANTE - UBICACIÓN GEOGRÁFICA:\n";
            $prompt .= "La empresa está ubicada en: " . ($empresa['ubicacion'] ?? 'N/A') . "\n";
            $prompt .= "DEBES proporcionar recomendaciones ESPECÍFICAS y REALES cercanas a esta ubicación.\n";
            $prompt .= "Por ejemplo: nombres reales de parques, plazas, centros comerciales, barrios estratégicos, o tipos específicos de proveedores que operan en esa zona.\n";
            $prompt .= "NO des respuestas genéricas. Sé GEOESPECÍFICO.\n\n";
        }
    } else {
        $prompt .= "⚠️ El usuario aún NO tiene empresa registrada. Si pregunta sobre campañas o estrategias, recomiéndale primero registrar su empresa.\n";
    }
    
    // Resumen contextual
    if ($resumenContexto['cantidad_mensajes'] > 1) {
        $prompt .= "CONTEXTO DE CONVERSACIÓN:\n";
        $prompt .= "- Tema principal: " . $resumenContexto['tema_conversacion'] . "\n";
        if (isset($resumenContexto['campanas_activas'])) {
            $prompt .= "- Tiene " . $resumenContexto['campanas_activas'] . " campaña(s) activa(s)\n";
        }
        $prompt .= "\n";
    }
    
    // Instrucciones específicas por intención
    $prompt .= "INSTRUCCIÓN CLAVE PARA ESTA CONSULTA:\n";
    $prompt .= $instruccionesIntencion . "\n";
    
    // Instrucciones generales de formato
    $prompt .= "\nINSTRUCCIONES DE FORMATO:\n";
    $prompt .= "1. SÉ BREVE: Máximo 2-3 párrafos cortos (no aumentes tamaño)\n";
    $prompt .= "2. USA EMOJIS: Coloca emoji relevante al inicio de puntos clave\n";
    $prompt .= "3. USA LISTAS: Para múltiples ideas usa viñetas (-)\n";
    $prompt .= "4. NO uses HTML, solo texto plano con markdown simple\n";
    $prompt .= "5. SÉ ESPECÍFICO: Evita respuestas genéricas, adapta al contexto del usuario\n";
    $prompt .= "6. GEOLOCALIZACIÓN: Si la pregunta involucra ubicación, proveedores o actividades, SÉ ESPECÍFICO con lugares REALES cercanos a: " . ($contextoEmpresa['tiene'] ? $contextoEmpresa['datos']['ubicacion'] : 'N/A') . "\n";
    
    return $prompt;
}

function llamarGeminiAPI($apiKey, $prompt) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent?key=$apiKey";
    
    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800,
            "topP" => 0.85,
            "topK" => 40
        ],
        "safetySettings" => [
            ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
            ["category" => "HARM_CATEGORY_HATE_SPEECH", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Error Gemini API: " . $response);
        return false;
    }
    
    $responseData = json_decode($response, true);
    return $responseData['candidates'][0]['content']['parts'][0]['text'] ?? false;
}

function formatearRespuestaHTML($texto) {
    $texto = trim($texto);
    $texto = str_replace("\r\n", "\n", $texto);
    $bloques = preg_split('/\n\n+/', $texto);
    $html = '';
    
    foreach ($bloques as $bloque) {
        $bloque = trim($bloque);
        if (empty($bloque)) continue;
        
        $lineas = explode("\n", $bloque);
        $esLista = false;
        
        foreach ($lineas as $linea) {
            if (preg_match('/^\s*[-*•]\s+/', $linea) || preg_match('/^\s*\p{So}\s+/u', $linea)) {
                $esLista = true;
                break;
            }
        }
        
        if ($esLista) {
            $html .= '<ul class="bot-list">';
            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if (empty($linea)) continue;
                $linea = preg_replace('/^[-*•]\s+/', '', $linea);
                $html .= '<li>' . nl2br(htmlspecialchars($linea)) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $bloque = nl2br(htmlspecialchars($bloque));
            $bloque = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $bloque);
            $bloque = preg_replace('/\*([^\*]+?)\*/', '<em>$1</em>', $bloque);
            $html .= '<p>' . $bloque . '</p>';
        }
    }
    
    return empty($html) ? '<p>' . nl2br(htmlspecialchars($texto)) . '</p>' : $html;
}

function guardarMensaje($conn, $usuario_id, $mensaje, $rol) {
    $sql = "INSERT INTO chatbot_conversaciones (usuario_id, mensaje, rol) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $usuario_id, $mensaje, $rol);
    $stmt->execute();
    $stmt->close();
}
?>