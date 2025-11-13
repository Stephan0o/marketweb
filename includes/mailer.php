<?php
// Cargar init.php
require_once __DIR__ . '/../init.php';

/**
 * Genera un código aleatorio de verificación
 */
function generarCodigo($longitud = 6) {
    return substr(str_shuffle("0123456789"), 0, $longitud);
}

/**
 * Envía correo usando SendGrid API
 */
function enviarCorreo($destinatario, $codigo) {
    try {
        // ============================================
        // OBTENER VARIABLES DE ENTORNO
        // ============================================
        
        $sendgrid_api_key = $_ENV['SENDGRID_API_KEY'] ?? getenv('SENDGRID_API_KEY');
        $mail_from_name = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?? 'MarketWeb';
        $mail_username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME') ?? 'noreply@marketweb.com';
        
        // Validar que exista API Key
        if (!$sendgrid_api_key) {
            throw new Exception('SENDGRID_API_KEY no configurada');
        }
        
        // ============================================
        // PREPARAR CONTENIDO DEL EMAIL
        // ============================================
        
        $subject = "Confirma tu Registro - MarketWeb";
        $html_body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fc; border-radius: 10px;'>
            <div style='background: linear-gradient(135deg, #4e73df, #1cc88a); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                <h2 style='color: white; margin: 0;'>¡Bienvenido a MarketWeb! 👋</h2>
            </div>
            <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                <p style='font-size: 16px; color: #2e2e3e;'>Gracias por registrarte en nuestra plataforma.</p>
                <p style='font-size: 16px; color: #2e2e3e;'>Para completar tu registro, usa el siguiente código de verificación:</p>
                <div style='background: #f8f9fc; border-left: 4px solid #4e73df; padding: 20px; margin: 20px 0; text-align: center;'>
                    <h1 style='color: #4e73df; margin: 0; font-size: 36px; letter-spacing: 5px;'>$codigo</h1>
                </div>
                <p style='font-size: 14px; color: #6c757d; margin-top: 20px;'>Este código expirará en 10 minutos.</p>
                <p style='font-size: 14px; color: #6c757d;'>Si no solicitaste este registro, ignora este correo.</p>
            </div>
            <div style='text-align: center; padding: 20px; font-size: 12px; color: #6c757d;'>
                <p>© " . date('Y') . " MarketWeb. Todos los derechos reservados.</p>
            </div>
        </div>
        ";
        
        $text_body = "Tu código de verificación es: $codigo";
        
        // ============================================
        // PREPARAR PAYLOAD PARA SENDGRID
        // ============================================
        
        $email_data = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $destinatario,
                            'name' => 'Usuario MarketWeb'
                        ]
                    ],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $mail_username,
                'name' => $mail_from_name
            ],
            'reply_to' => [
                'email' => $mail_username,
                'name' => $mail_from_name
            ],
            'content' => [
                [
                    'type' => 'text/plain',
                    'value' => $text_body
                ],
                [
                    'type' => 'text/html',
                    'value' => $html_body
                ]
            ]
        ];
        
        // ============================================
        // ENVIAR VÍA SENDGRID API
        // ============================================
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($email_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $sendgrid_api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Validar respuesta
        if ($curl_error) {
            throw new Exception('Error cURL: ' . $curl_error);
        }
        
        if ($http_code !== 202) {
            error_log("SendGrid Error ($http_code): " . $response);
            throw new Exception('SendGrid retornó código: ' . $http_code);
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Error en envío de email: " . $e->getMessage());
        return [
            'success' => false, 
            'error' => $e->getMessage(),
            'message' => 'Error al enviar correo'
        ];
    }
}

?>