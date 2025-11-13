<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cargar init.php
require_once __DIR__ . '/../init.php';

//codigo aleatorio
function generarCodigo($longitud = 6) {
    return substr(str_shuffle("0123456789"), 0, $longitud);
}

//enviar correo
function enviarCorreo($destinatario, $codigo) {
    $mail = new PHPMailer(true);
    
    try {
        $mail_host = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?? 'smtp.gmail.com';
        $mail_port = $_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?? 587;
        $mail_username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME');
        $mail_password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD');
        $mail_from_name = $_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?? 'MarketWeb';
        
        if (!$mail_username || !$mail_password) {
            throw new Exception('Credenciales de email no configuradas');
        }
        
        // config SMTP
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = $mail_host;
        $mail->SMTPAuth = true;
        $mail->Username = $mail_username;
        $mail->Password = $mail_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mail_port;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = true;

        // config correo
        $mail->setFrom($mail_username, $mail_from_name);
        $mail->addAddress($destinatario);
        $mail->isHTML(true);
        $mail->Subject = "Confirma tu Registro - MarketWeb";
        
        $mail->Body = "
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
        
        $mail->AltBody = "Tu código de verificación es: $codigo";

        $mail->send();
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("Error en PHPMailer: " . $mail->ErrorInfo);
        return [
            'success' => false, 
            'error' => $mail->ErrorInfo,
            'message' => 'Error al enviar correo: ' . $e->getMessage()
        ];
    }
}

?>