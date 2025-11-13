<?php
// Verificar que las variables de sesión estén disponibles
if (!isset($_SESSION['usuario_nombre'])) {
    return; // No mostrar el chatbot si no hay sesión
}

$chat_nombre = $_SESSION['usuario_nombre'];
$chat_usuario_id = $_SESSION['usuario_id'];
$chat_rol = $_SESSION['usuario_rol'];

// Verificar si tiene empresa registrada
$chat_tiene_empresa = false;
$chat_empresa_id = null;

$sql_empresa = "SELECT id FROM empresas WHERE usuario_id = ? LIMIT 1";
$stmt_empresa = $conn->prepare($sql_empresa);
$stmt_empresa->bind_param("i", $chat_usuario_id);
$stmt_empresa->execute();
$result_empresa = $stmt_empresa->get_result();
if ($result_empresa->num_rows > 0) {
    $chat_tiene_empresa = true;
    $chat_empresa_id = $result_empresa->fetch_assoc()['id'];
}
$stmt_empresa->close();

// Verificar si tiene campañas registradas
$chat_tiene_campanas = false;
if ($chat_tiene_empresa) {
    $sql_campanas = "SELECT id FROM campañas WHERE empresa_id = ? LIMIT 1";
    $stmt_campanas = $conn->prepare($sql_campanas);
    $stmt_campanas->bind_param("i", $chat_empresa_id);
    $stmt_campanas->execute();
    $result_campanas = $stmt_campanas->get_result();
    $chat_tiene_campanas = $result_campanas->num_rows > 0;
    $stmt_campanas->close();
}
?>

<!-- Widget del Chatbot -->
<div id="chatbot-container">
    <!-- Botón flotante con animación -->
    <div id="chatbot-button" class="chatbot-btn">
        <div class="chatbot-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </div>
        <div class="chatbot-pulse"></div>
    </div>

    <!-- Mensaje de bienvenida flotante -->
    <div id="chatbot-welcome" class="chatbot-welcome">
        <div class="welcome-content">
            <span class="welcome-emoji">👋</span>
            <p class="welcome-text">¡Hola! Soy tu asistente de marketing</p>
        </div>
        <button class="welcome-close" onclick="closeChatWelcome()">×</button>
    </div>

    <!-- Ventana del chat -->
    <div id="chatbot-window" class="chatbot-window">
        <!-- Header del chat -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <div class="chatbot-header-text">
                    <h4>Asistente MarketWeb</h4>
                    <span class="chatbot-status">
                        <span class="status-dot"></span>
                        En línea
                    </span>
                </div>
            </div>
            <div class="chatbot-header-actions">
                <button class="chatbot-history-btn" onclick="toggleHistory()" title="Historial">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </button>
                <button class="chatbot-minimize" onclick="minimizeChat()" title="Minimizar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
                <button class="chatbot-close" onclick="closeChat()" title="Cerrar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Cuerpo del chat (mensajes) -->
        <div class="chatbot-body" id="chatbot-messages">
            <!-- Mensaje inicial del bot -->
            <div class="message bot-message">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <div class="message-content">
                    <div class="message-bubble">
                        <p>¡Hola <strong><?php echo htmlspecialchars($chat_nombre); ?></strong>! 👋</p>
                        <p>Soy tu asistente inteligente de marketing. Puedo ayudarte con:</p>
                        <ul>
                            <li>💡 Generar ideas de campañas</li>
                            <li>📊 Analizar estrategias</li>
                            <li>🎯 Optimizar tu público objetivo</li>
                            <li>📱 Sugerir contenido para redes sociales</li>
                        </ul>
                        <p>¿En qué puedo ayudarte hoy?</p>
                    </div>
                    <span class="message-time"><?php echo date('H:i'); ?></span>
                </div>
            </div>

            <!-- Sugerencias rápidas -->
            <div class="quick-suggestions">
                <button class="suggestion-btn" onclick="sendQuickMessage('¿Cómo crear una campaña efectiva?')">
                    🎯 ¿Cómo crear una campaña?
                </button>
                <button class="suggestion-btn" onclick="sendQuickMessage('Dame ideas para aumentar ventas')">
                    💰 Ideas para ventas
                </button>
                <button class="suggestion-btn" onclick="sendQuickMessage('¿Qué redes sociales usar?')">
                    📱 ¿Qué redes usar?
                </button>
            </div>
        </div>

        <!-- Footer del chat (input) -->
        <div class="chatbot-footer">
            <form id="chatbot-form" onsubmit="sendMessage(event)">
                <div class="chatbot-input-wrapper">
                    <textarea 
                        id="chatbot-input" 
                        class="chatbot-input" 
                        placeholder="Escribe tu mensaje..."
                        rows="1"
                        maxlength="500"
                    ></textarea>
                    <button type="submit" class="chatbot-send-btn" id="send-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </form>
            <div class="chatbot-footer-info">
                <small>Powered by IA • MarketWeb</small>
            </div>
        </div>

        <!-- Indicador de escritura -->
        <div class="typing-indicator" id="typing-indicator" style="display: none;">
            <div class="message bot-message">
                <div class="message-avatar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                        <line x1="9" y1="9" x2="9.01" y2="9"></line>
                        <line x1="15" y1="9" x2="15.01" y2="9"></line>
                    </svg>
                </div>
                <div class="message-content">
                    <div class="message-bubble typing">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Historial -->
        <div class="history-modal" id="history-modal">
            <div class="history-header">
                <h3>📜 Historial de Conversaciones</h3>
                <button class="history-close" onclick="closeHistory()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="history-body" id="history-content">
                <div class="history-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <p>Cargando historial...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Incluir CSS y JS del chatbot -->
<link rel="stylesheet" href="assets/css/chatbot.css">
<script src="assets/js/chatbot.js"></script>

<!-- Variables PHP para JavaScript -->
<script>
    const chatbotConfig = {
        userName: "<?php echo htmlspecialchars($chat_nombre); ?>",
        userId: <?php echo $chat_usuario_id; ?>,
        userRole: "<?php echo htmlspecialchars($chat_rol); ?>",
        hasCompany: <?php echo json_encode($chat_tiene_empresa); ?>,
        hasCampaigns: <?php echo json_encode($chat_tiene_campanas); ?>
    };
</script>