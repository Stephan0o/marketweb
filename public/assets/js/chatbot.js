// Estado del chatbot
let chatbotState = {
    isOpen: false,
    isWelcomeVisible: true,
    messageHistory: []
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initChatbot();
});

// INICIALIZACIÓN
function initChatbot() {
    const chatbotBtn = document.getElementById('chatbot-button');
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatbotInput = document.getElementById('chatbot-input');
    
    if (!chatbotBtn || !chatbotInput) return; // Seguridad
    
    // Event listeners
    chatbotBtn.addEventListener('click', toggleChat);
    
    // Auto-resize del textarea
    chatbotInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Mostrar mensaje de bienvenida después de 2 segundos
    setTimeout(() => {
        showWelcomeMessage();
    }, 2000);

    // Auto-ocultar mensaje de bienvenida después de 10 segundos
    setTimeout(() => {
        hideWelcomeMessage();
    }, 12000);
}

// TOGGLE CHAT 
function toggleChat() {
    const chatbotWindow = document.getElementById('chatbot-window');
    
    if (!chatbotState.isOpen) {
        openChat();
    } else {
        closeChat();
    }
}

function openChat() {
    const chatbotWindow = document.getElementById('chatbot-window');
    const chatbotWelcome = document.getElementById('chatbot-welcome');
    
    if (!chatbotWindow) return;
    
    chatbotWindow.classList.add('active');
    chatbotState.isOpen = true;
    
    // Ocultar mensaje de bienvenida
    if (chatbotWelcome) {
        chatbotWelcome.style.display = 'none';
        chatbotState.isWelcomeVisible = false;
    }
    
    setTimeout(() => {
        const input = document.getElementById('chatbot-input');
        if (input) input.focus();
    }, 300);
    
    // Scroll al final
    scrollToBottom();
}

function closeChat() {
    const chatbotWindow = document.getElementById('chatbot-window');
    if (!chatbotWindow) return;
    
    chatbotWindow.classList.remove('active');
    chatbotState.isOpen = false;
}

function minimizeChat() {
    closeChat();
}

// MENSAJES DE BIENVENIDA
function showWelcomeMessage() {
    const chatbotWelcome = document.getElementById('chatbot-welcome');
    if (chatbotWelcome && !chatbotState.isOpen) {
        chatbotWelcome.style.display = 'flex';
        chatbotState.isWelcomeVisible = true;
    }
}

function hideWelcomeMessage() {
    const chatbotWelcome = document.getElementById('chatbot-welcome');
    if (chatbotWelcome && chatbotState.isWelcomeVisible && !chatbotState.isOpen) {
        chatbotWelcome.style.display = 'none';
        chatbotState.isWelcomeVisible = false;
    }
}

function closeChatWelcome() {
    hideWelcomeMessage();
}

// ENVIAR MENSAJES
function sendMessage(event) {
    event.preventDefault();
    
    const input = document.getElementById('chatbot-input');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Agregar mensaje del usuario
    addUserMessage(message);
    
    // Limpiar input
    input.value = '';
    input.style.height = 'auto';
    
    // Guardar en historial
    chatbotState.messageHistory.push({
        role: 'user',
        content: message,
        timestamp: new Date()
    });
    
    // Indicador de escritura
    showTypingIndicator();
    
    processUserMessage(message);
}

function sendQuickMessage(message) {
    const input = document.getElementById('chatbot-input');
    if (!input) return;
    
    input.value = message;
    
    // Ocultar sugerencias
    const suggestions = document.querySelector('.quick-suggestions');
    if (suggestions) {
        suggestions.style.display = 'none';
    }
    
    // Enviar mensaje
    const form = document.getElementById('chatbot-form');
    if (form) {
        form.dispatchEvent(new Event('submit'));
    }
}

// AGREGAR MENSAJES AL CHAT
function addUserMessage(message, customTime = null) {
    const messagesContainer = document.getElementById('chatbot-messages');
    if (!messagesContainer) return;
    
    const time = customTime || getCurrentTime();
    
    const messageHTML = `
        <div class="message user-message">
            <div class="message-content">
                <div class="message-bubble">
                    <p>${escapeHtml(message)}</p>
                </div>
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    scrollToBottom();
}

function addBotMessage(message, customTime = null) {
    const messagesContainer = document.getElementById('chatbot-messages');
    if (!messagesContainer) return;
    
    const time = customTime || getCurrentTime();
    
    const messageHTML = `
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
                    ${message}
                </div>
                <span class="message-time">${time}</span>
            </div>
        </div>
    `;
    
    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
    
    // Guardar en historial
    chatbotState.messageHistory.push({
        role: 'bot',
        content: message,
        timestamp: new Date()
    });
    
    scrollToBottom();
}

// INDICADOR DE ESCRITURA
function showTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
        typingIndicator.style.display = 'block';
        scrollToBottom();
    }
}

function hideTypingIndicator() {
    const typingIndicator = document.getElementById('typing-indicator');
    if (typingIndicator) {
        typingIndicator.style.display = 'none';
    }
}

// PROCESAR MENSAJES CON IA
function processUserMessage(message) {
    const sendBtn = document.getElementById('send-btn');
    if (sendBtn) sendBtn.disabled = true;
    
    const historialParaEnviar = chatbotState.messageHistory
        .slice(-10)
        .map(msg => ({
            role: msg.role,
            content: typeof msg.content === 'string' ? msg.content.replace(/<[^>]*>/g, '') : msg.content
        }));
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 segundos timeout
    
    fetch('../backend/API/chatbot_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            history: historialParaEnviar
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        hideTypingIndicator();
        
        if (data.success) {
            addBotMessage(data.message);
        } else {
            const errorMsg = data.error || 'Error desconocido';
            addBotMessage(`<p>Lo siento, hubo un error: ${escapeHtml(errorMsg)}</p>`);
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        hideTypingIndicator();
        
        let errorMessage = '⚠️ Error al conectar con el servidor';
        
        if (error.name === 'AbortError') {
            errorMessage = '⏱️ Timeout: El servidor tardó demasiado en responder';
        } else if (error instanceof TypeError) {
            errorMessage = '🔌 Error de conexión. Verifica tu internet';
        }
        
        console.error('Error en processUserMessage:', error);
        addBotMessage(`<p>${errorMessage}</p>`);
    })
    .finally(() => {
        if (sendBtn) sendBtn.disabled = false;
    });
}

// Funciones
function scrollToBottom() {
    const messagesContainer = document.getElementById('chatbot-messages');
    if (!messagesContainer) return;
    
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

function getCurrentTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// GUARDAR CONVERSACIÓN
function saveConversation() {
    const data = {
        userId: typeof chatbotConfig !== 'undefined' ? chatbotConfig.userId : null,
        messages: chatbotState.messageHistory,
        timestamp: new Date()
    };
    
    try {
        localStorage.setItem('chatbot_history', JSON.stringify(data));
    } catch (e) {
        console.log('No se pudo guardar el historial');
    }
}

// CARGAR HISTORIAL
function loadConversationHistory() {
    try {
        const savedHistory = localStorage.getItem('chatbot_history');
        if (savedHistory) {
            const data = JSON.parse(savedHistory);
            chatbotState.messageHistory = data.messages || [];
        }
    } catch (e) {
        console.log('No se pudo cargar el historial');
    }
}

// Guardar conversación al cerrar la página
window.addEventListener('beforeunload', saveConversation);

// HISTORIAL
function toggleHistory() {
    const historyModal = document.getElementById('history-modal');
    if (!historyModal) return;
    
    const isActive = historyModal.classList.contains('active');
    
    if (isActive) {
        closeHistory();
    } else {
        openHistory();
    }
}

// Abrir modal de historial
function openHistory() {
    const historyModal = document.getElementById('history-modal');
    if (!historyModal) return;
    
    historyModal.classList.add('active');
    cargarHistorial();
}

// Cerrar modal de historial
function closeHistory() {
    const historyModal = document.getElementById('history-modal');
    if (historyModal) {
        historyModal.classList.remove('active');
    }
}

// Cargar historial desde el servidor
function cargarHistorial() {
    const historyContent = document.getElementById('history-content');
    if (!historyContent) return;
    
    // Mostrar loading
    historyContent.innerHTML = `
        <div class="history-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <p>Cargando historial...</p>
        </div>
    `;
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    fetch('../backend/API/chatbot_historial_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'obtener_historial'
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) throw new Error(`Error HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        if (data.success && data.historial && data.historial.length > 0) {
            mostrarHistorial(data.historial);
        } else {
            mostrarHistorialVacio();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Error cargando historial:', error);
        
        if (error.name === 'AbortError') {
            mostrarErrorHistorial('Timeout al cargar el historial');
        } else {
            mostrarErrorHistorial();
        }
    });
}

// Mostrar historial en el DOM
function mostrarHistorial(historial) {
    const historyContent = document.getElementById('history-content');
    if (!historyContent) return;
    
    let html = '';
    let mesActual = '';
    
    historial.forEach(item => {
        const fecha = new Date(item.fecha);
        const mes = fecha.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
        
        // Header de mes
        if (mes !== mesActual) {
            mesActual = mes;
            html += `<div class="history-date">${mes}</div>`;
        }
        
        // Item de conversación
        const fechaFormato = fecha.toLocaleDateString('es-ES', { 
            day: 'numeric', 
            month: 'short' 
        });
        
        html += `
            <div class="history-item" onclick="verConversacion('${item.fecha}')">
                <div class="history-item-header">
                    <strong>${fechaFormato}</strong>
                    <span class="history-item-time">${item.ultima_hora}</span>
                </div>
                <div class="history-item-preview">
                    ${escapeHtml(item.preview)}...
                </div>
                <small style="color: #6c757d; font-size: 0.75rem;">
                    ${item.total_mensajes} mensaje${item.total_mensajes > 1 ? 's' : ''}
                </small>
            </div>
        `;
    });
    
    historyContent.innerHTML = html;
}

// Mostrar mensaje de historial vacío
function mostrarHistorialVacio() {
    const historyContent = document.getElementById('history-content');
    if (!historyContent) return;
    
    historyContent.innerHTML = `
        <div class="history-empty">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <p>No hay conversaciones anteriores</p>
            <small style="color: #6c757d;">Comienza una conversación para ver tu historial</small>
        </div>
    `;
}

// Mostrar error al cargar historial
function mostrarErrorHistorial(mensaje = null) {
    const historyContent = document.getElementById('history-content');
    if (!historyContent) return;
    
    historyContent.innerHTML = `
        <div class="history-empty">
            <p style="color: #e74a3b;">⚠️ ${mensaje || 'Error al cargar el historial'}</p>
            <button onclick="cargarHistorial()" style="margin-top: 10px; padding: 8px 16px; background: #4e73df; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">
                🔄 Reintentar
            </button>
        </div>
    `;
}

// Ver una conversación específica
function verConversacion(fecha) {
    const messagesContainer = document.getElementById('chatbot-messages');
    if (!messagesContainer) return;
    
    // Cerrar modal de historial
    closeHistory();
    
    // Limpiar chat actual
    messagesContainer.innerHTML = '';
    
    // Mostrar loading
    showTypingIndicator();
    
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);
    
    // Cargar conversación
    fetch('../backend/API/chatbot_historial_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'obtener_conversacion',
            fecha: fecha
        }),
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) throw new Error(`Error HTTP ${response.status}`);
        return response.json();
    })
    .then(data => {
        hideTypingIndicator();
        
        if (data.success) {
            const fechaFormato = new Date(data.fecha).toLocaleDateString('es-ES', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            messagesContainer.innerHTML = `
                <div style="text-align: center; padding: 15px; background: white; border-radius: 10px; margin-bottom: 15px;">
                    <small style="color: #6c757d; font-weight: 600;">
                        📅 Conversación del ${fechaFormato}
                    </small>
                </div>
            `;
            
            // Mostrar mensajes
            data.mensajes.forEach(msg => {
                if (msg.rol === 'user') {
                    addUserMessage(msg.mensaje, msg.hora);
                } else {
                    addBotMessage(msg.mensaje, msg.hora);
                }
            });
            
            scrollToBottom();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        hideTypingIndicator();
        console.error('Error:', error);
        addBotMessage('<p>⚠️ Error al cargar la conversación</p>');
    });
}

// proteccion contra extensiones
if (typeof chrome !== 'undefined' && chrome.runtime && chrome.runtime.onMessage) {
    chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
        try {
            sendResponse({ status: 'ok', received: true });
        } catch (error) {
            try {
                sendResponse({ status: 'error', message: error.message });
            } catch (e) {
            }
        }
        return false;
    });
}

// Prevenir mensajes del content script de extensiones
window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    
    if (event.data && event.data.type === 'FROM_EXTENSION') {
        window.postMessage({
            type: 'FROM_PAGE',
            status: 'received'
        }, '*');
    }
}, false);