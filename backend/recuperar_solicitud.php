<?php
require_once 'config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header("Location: ../view/recuperar.php");
    exit();
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
    $_SESSION['errores_recuperar'] = ["Por favor ingresa un correo electrónico válido."];
    header("Location: ../view/recuperar.php");
    exit();
}

try {
    // Verificar si el email existe
    $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if($usuario) {
        // Generar token único y fecha de expiración (1 hora)
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Guardar token en BD
        $update = $pdo->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?");
        $update->execute([$token, $expiracion, $usuario['id']]);

        // Detectar URL base dinámicamente (funciona en localhost y producción)
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        // Obtener ruta relativa del proyecto (ej: /Notas o / si está en raíz)
        $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
        $path = str_replace('\\', '/', $path); // Corrección para Windows
        if ($path === '/' || $path === '.') $path = ''; 
        
        $link = $protocol . "://" . $host . $path . "/view/restablecer.php?token=" . $token;
        
        $asunto = "Recuperar Password - Notas App";
        
        // Construir mensaje HTML
        $mensaje = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
            <div style="background-color: #f4f4f4; padding: 40px 0;">
                <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2 style="color: #667eea; margin: 0; font-size: 24px;">Sistema de Notas</h2>
                    </div>
                    <div style="color: #333333; font-size: 16px; line-height: 1.6;">
                        <p>Hola <strong>' . htmlspecialchars($usuario['username']) . '</strong>,</p>
                        <p>Hemos recibido una solicitud para restablecer la contraseña de tu cuenta. Haz clic en el siguiente botón para crear una nueva:</p>
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . $link . '" style="background-color: #667eea; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">Restablecer Contraseña</a>
                        </div>
                        <p>Este enlace expirará en 1 hora.</p>
                        <p style="font-size: 14px; color: #777; margin-top: 30px;">Si no solicitaste este cambio, puedes ignorar este correo.</p>
                    </div>
                    <div style="border-top: 1px solid #eeeeee; margin-top: 30px; padding-top: 20px; text-align: center; font-size: 12px; color: #999999;">
                        <p>&copy; ' . date('Y') . ' Sistema de Notas</p>
                    </div>
                </div>
            </div>
        </body>
        </html>';

        // Cabeceras para enviar HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: no-reply@" . $host . "\r\n";
        $headers .= "Reply-To: no-reply@" . $host . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Intentar enviar
        @mail($email, $asunto, $mensaje, $headers);
    }

    $_SESSION['mensaje_exito'] = "Si el correo existe, se han enviado las instrucciones. ¡Por favor revisa tu carpeta de Spam!";
    header("Location: ../view/login.php");

} catch(PDOException $e) {
    $_SESSION['errores_recuperar'] = ["Error en el sistema: " . $e->getMessage()];
    header("Location: ../view/recuperar.php");
}
?>