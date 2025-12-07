<?php
session_start();
require_once __DIR__ . '../../init-light.php';

// Verificar autenticación
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION["usuario_id"];
$nombre = $_SESSION["usuario_nombre"];
$rol = $_SESSION["usuario_rol"];

// Obtener estadísticas
$sql_stats = "SELECT 
                COUNT(*) as total_mensajes,
                COUNT(DISTINCT DATE(timestamp)) as dias_activos,
                MIN(DATE(timestamp)) as primera_conversacion
              FROM chatbot_conversaciones 
              WHERE usuario_id = ?";

$stmt = $conn->prepare($sql_stats);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Conversaciones - MarketWeb</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/historial_conversaciones.css" rel="stylesheet">

</head>
<body>

    <div class="history-page">
        <div class="history-container">
            <!-- Botón volver -->
            <a href="panel.php" class="back-button">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Volver al Panel
            </a>
            
            <!-- estadíticas -->
            <div class="history-header-section">
                <h1 style="margin: 0 0 10px 0; color: #2e2e3e;">📜 Historial de Conversaciones</h1>
                <p style="color: #6c757d; margin: 0;">Revisa todas tus conversaciones con el asistente de marketing</p>
                
                <div class="stats-grid">
                    <div class="stat-box">
                        <h3><?php echo number_format($stats['total_mensajes']); ?></h3>
                        <p>Mensajes totales</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo $stats['dias_activos']; ?></h3>
                        <p>Días con conversaciones</p>
                    </div>
                    <div class="stat-box">
                        <h3><?php 
                            if ($stats['primera_conversacion']) {
                                $fecha = new DateTime($stats['primera_conversacion']);
                                echo $fecha->format('d/m/Y');
                            } else {
                                echo '-';
                            }
                        ?></h3>
                        <p>Primera conversación</p>
                    </div>
                </div>
            </div>
            
            <!-- Barra de búsqueda -->
            <div class="search-bar">
                <input 
                    type="text" 
                    class="search-input" 
                    id="search-input"
                    placeholder="🔍 Buscar en el historial..."
                    onkeyup="buscarConversaciones()"
                >
            </div>
            
            <!-- Lista de conversaciones -->
            <div class="conversations-list">
                <div id="conversations-container">
                    <div class="loading">
                        <p>Cargando conversaciones...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para ver conversación completa -->
    <div class="modal-conversation" id="modal-conversation">
        <div class="modal-content-custom">
            <div class="modal-header-custom">
                <h3 id="modal-title" style="margin: 0;">Conversación</h3>
                <button onclick="cerrarModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body-custom" id="modal-body">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Cargar conversaciones
    document.addEventListener('DOMContentLoaded', function() {
        cargarConversaciones();
    });

    // Cargar todas las conversaciones
    function cargarConversaciones() {
        fetch('../backend/API/chatbot_historial_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'obtener_historial'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.historial.length > 0) {
                mostrarConversaciones(data.historial);
            } else {
                mostrarVacio();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError();
        });
    }

    // Mostrar conversaciones
    function mostrarConversaciones(conversaciones) {
        const container = document.getElementById('conversations-container');
        let html = '';
        
        conversaciones.forEach(conv => {
            const fecha = new Date(conv.fecha);
            const fechaFormato = fecha.toLocaleDateString('es-ES', { 
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            html += `
                <div class="conversation-item" onclick="verConversacionCompleta('${conv.fecha}', '${fechaFormato}')">
                    <div class="conversation-date">
                        <strong>${fechaFormato}</strong>
                        <button class="btn-delete" onclick="event.stopPropagation(); eliminarConversacion('${conv.fecha}')">
                            🗑️ Eliminar
                        </button>
                    </div>
                    <div class="conversation-preview">
                        ${conv.preview}...
                    </div>
                    <div class="conversation-meta">
                        <span>⏰ ${conv.ultima_hora}</span>
                        <span>💬 ${conv.total_mensajes} mensaje${conv.total_mensajes > 1 ? 's' : ''}</span>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Mostrar estado vacío
    function mostrarVacio() {
        const container = document.getElementById('conversations-container');
        container.innerHTML = `
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>No hay conversaciones</h3>
                <p>Comienza a chatear con el asistente para ver tu historial aquí</p>
            </div>
        `;
    }

    // Mostrar error
    function mostrarError() {
        const container = document.getElementById('conversations-container');
        container.innerHTML = `
            <div class="empty-state">
                <p style="color: #e74a3b;">⚠️ Error al cargar las conversaciones</p>
                <button onclick="cargarConversaciones()" style="margin-top: 15px; padding: 10px 20px; background: #4e73df; color: white; border: none; border-radius: 8px; cursor: pointer;">
                    Reintentar
                </button>
            </div>
        `;
    }

    // Ver conversación completa en modal
    function verConversacionCompleta(fecha, fechaFormato) {
        const modal = document.getElementById('modal-conversation');
        const modalTitle = document.getElementById('modal-title');
        const modalBody = document.getElementById('modal-body');
        
        modalTitle.textContent = fechaFormato;
        modalBody.innerHTML = '<div class="loading"><p>Cargando conversación...</p></div>';
        modal.classList.add('active');
        
        fetch('../backend/API/chatbot_historial_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'obtener_conversacion',
                fecha: fecha
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                data.mensajes.forEach(msg => {
                    html += `
                        <div class="message-item ${msg.rol}">
                            <div class="message-bubble-modal">
                                ${msg.mensaje}
                                <div style="font-size: 0.75rem; opacity: 0.7; margin-top: 5px;">
                                    ${msg.hora}
                                </div>
                            </div>
                        </div>
                    `;
                });
                modalBody.innerHTML = html;
                modalBody.scrollTop = modalBody.scrollHeight;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            modalBody.innerHTML = '<div class="empty-state"><p style="color: #e74a3b;">Error al cargar la conversación</p></div>';
        });
    }

    // Cerrar modal
    function cerrarModal() {
        const modal = document.getElementById('modal-conversation');
        modal.classList.remove('active');
    }

    // Eliminar conversación
    function eliminarConversacion(fecha) {
        if (!confirm('¿Estás seguro de eliminar esta conversación? Esta acción no se puede deshacer.')) {
            return;
        }
        
        fetch('../backend/API/chatbot_historial_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'eliminar_conversacion',
                fecha: fecha
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Conversación eliminada correctamente');
                cargarConversaciones();
            } else {
                alert('❌ Error al eliminar la conversación');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error al eliminar la conversación');
        });
    }

    // Buscar conversaciones
    let timeoutBusqueda;
    function buscarConversaciones() {
        clearTimeout(timeoutBusqueda);
        const termino = document.getElementById('search-input').value.trim();
        
        if (termino.length < 2) {
            cargarConversaciones();
            return;
        }
        
        timeoutBusqueda = setTimeout(() => {
            fetch('../backend/API/chatbot_historial_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'buscar',
                    termino: termino
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.resultados.length > 0) {
                    mostrarResultadosBusqueda(data.resultados);
                } else {
                    mostrarSinResultados();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }, 500);
    }

    // Mostrar resultados de búsqueda
    function mostrarResultadosBusqueda(resultados) {
        const container = document.getElementById('conversations-container');
        let html = '<div style="padding: 15px; background: #e7f3ff; border-radius: 10px; margin-bottom: 15px;">';
        html += `<strong>🔍 ${resultados.length} resultado${resultados.length > 1 ? 's' : ''} encontrado${resultados.length > 1 ? 's' : ''}</strong>`;
        html += '</div>';
        
        resultados.forEach(res => {
            const fecha = new Date(res.fecha);
            const fechaFormato = fecha.toLocaleDateString('es-ES', { 
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
            
            html += `
                <div class="conversation-item" onclick="verConversacionCompleta('${res.fecha}', '${fechaFormato}')">
                    <div class="conversation-date">
                        <strong>${fechaFormato} - ${res.hora}</strong>
                        <span style="background: #4e73df; color: white; padding: 3px 8px; border-radius: 12px; font-size: 0.75rem;">
                            ${res.rol === 'user' ? 'Tú' : 'Bot'}
                        </span>
                    </div>
                    <div class="conversation-preview">
                        ${res.mensaje.substring(0, 200)}...
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }

    // Mostrar sin resultados
    function mostrarSinResultados() {
        const container = document.getElementById('conversations-container');
        container.innerHTML = `
            <div class="empty-state">
                <p>🔍 No se encontraron resultados</p>
                <small>Intenta con otros términos de búsqueda</small>
            </div>
        `;
    }

    // Cerrar modal al hacer clic fuera
    document.getElementById('modal-conversation').addEventListener('click', function(e) {
        if (e.target === this) {
            cerrarModal();
        }
    });
    </script>

</body>
</html>